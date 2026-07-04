<?php

namespace App\Crm\Services\Settings;

use App\Crm\Models\CrmSetting;
use App\Crm\Services\Audit\CrmAuditLogger;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrmSettingsManager
{
    public function __construct(private readonly CrmAuditLogger $audit) {}

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $values = $this->storedValues();

        return collect($this->defaults())
            ->mapWithKeys(fn (mixed $default, string $key): array => [$key => $values[$key] ?? $default])
            ->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values, ?UploadedFile $logo = null, ?Authenticatable $user = null): void
    {
        $before = $this->all();
        $values = $this->normalize($values);

        if ($logo) {
            $values['company_logo_path'] = $this->storeLogo($logo);
        }

        foreach ($values as $key => $value) {
            CrmSetting::query()->updateOrCreate(
                [
                    'organization_id' => null,
                    'key' => $key,
                ],
                [
                    'group' => $this->groupFor($key),
                    'value' => ['value' => $value],
                    'type' => $this->typeFor($key),
                    'is_encrypted' => false,
                    'updated_by' => $user?->getAuthIdentifier(),
                    'created_by' => $user?->getAuthIdentifier(),
                ]
            );
        }

        Cache::forget($this->cacheKey());
        $after = $this->all();
        $changes = $this->audit->diff($before, $after);

        if ($changes['new'] !== []) {
            $this->audit->record('crm.settings.changed', null, $user, $changes['old'], $changes['new'], [
                'changed_keys' => array_keys($changes['new']),
            ]);
        }
    }

    public function logoPath(): ?string
    {
        $path = $this->get('company_logo_path');

        if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        $configured = config('crm.quotes.company.logo_path');

        if (! is_string($configured) || $configured === '') {
            return null;
        }

        $absolutePath = str_starts_with($configured, '/') ? $configured : public_path($configured);

        return is_file($absolutePath) ? $absolutePath : null;
    }

    public function logoUrl(): ?string
    {
        $path = $this->get('company_logo_path');

        return is_string($path) && $path !== '' && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    /**
     * @return array<string, string|null>
     */
    public function companyProfile(): array
    {
        return [
            'name' => (string) $this->get('company_name', config('app.name', 'CRM')),
            'address' => $this->nullableString('company_address'),
            'phone' => $this->nullableString('company_phone'),
            'email' => $this->nullableString('company_email'),
            'website' => config('crm.quotes.company.website'),
            'tax_office' => $this->nullableString('tax_office'),
            'tax_number' => $this->nullableString('tax_number'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'company_name' => config('crm.quotes.company.name') ?: config('app.name', 'CRM'),
            'company_logo_path' => null,
            'company_email' => config('crm.quotes.company.email'),
            'company_phone' => config('crm.quotes.company.phone'),
            'company_address' => config('crm.quotes.company.address'),
            'tax_number' => config('crm.quotes.company.tax_number'),
            'tax_office' => config('crm.quotes.company.tax_office'),
            'default_currency' => config('crm.money.default_currency', 'TRY'),
            'default_tax_rate' => (float) config('crm.money.default_tax_rate', 20),
            'quote_prefix' => config('crm.quotes.number_prefix', 'CRM-'),
            'quote_terms' => config('crm.quotes.default_terms'),
            'notify_email_enabled' => (bool) config('crm.notifications.email_enabled', true),
            'notify_task_reminders' => (bool) config('crm.notifications.task_reminders', true),
            'notify_task_assignments' => true,
            'notify_quote_status_changes' => (bool) config('crm.notifications.quote_status_changes', true),
            'notify_deal_closed' => (bool) config('crm.notifications.deal_closed', true),
            'notify_import_status_updates' => true,
            'notify_weekly_digest' => (bool) config('crm.notifications.weekly_digest', true),
            'ai_enabled' => (bool) config('crm.ai.enabled', false),
            'ai_driver' => config('crm.ai.driver', config('crm.ai.provider', 'openai')),
            'ai_model' => config('crm.ai.model'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storedValues(): array
    {
        if (! Schema::hasTable('crm_settings')) {
            return [];
        }

        return Cache::rememberForever($this->cacheKey(), function (): array {
            return CrmSetting::query()
                ->whereNull('organization_id')
                ->get(['key', 'value'])
                ->mapWithKeys(fn (CrmSetting $setting): array => [
                    $setting->key => data_get($setting->value, 'value'),
                ])
                ->all();
        });
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalize(array $values): array
    {
        return [
            'company_name' => trim((string) $values['company_name']),
            'company_email' => $this->blankToNull($values['company_email'] ?? null),
            'company_phone' => $this->blankToNull($values['company_phone'] ?? null),
            'company_address' => $this->blankToNull($values['company_address'] ?? null),
            'tax_number' => $this->blankToNull($values['tax_number'] ?? null),
            'tax_office' => $this->blankToNull($values['tax_office'] ?? null),
            'default_currency' => strtoupper((string) $values['default_currency']),
            'default_tax_rate' => (float) $values['default_tax_rate'],
            'quote_prefix' => (string) $values['quote_prefix'],
            'quote_terms' => $this->blankToNull($values['quote_terms'] ?? null),
            'notify_email_enabled' => (bool) ($values['notify_email_enabled'] ?? false),
            'notify_task_reminders' => (bool) ($values['notify_task_reminders'] ?? false),
            'notify_task_assignments' => (bool) ($values['notify_task_assignments'] ?? false),
            'notify_quote_status_changes' => (bool) ($values['notify_quote_status_changes'] ?? false),
            'notify_deal_closed' => (bool) ($values['notify_deal_closed'] ?? false),
            'notify_import_status_updates' => (bool) ($values['notify_import_status_updates'] ?? false),
            'notify_weekly_digest' => (bool) ($values['notify_weekly_digest'] ?? false),
            'ai_enabled' => (bool) ($values['ai_enabled'] ?? false),
            'ai_driver' => (string) $values['ai_driver'],
            'ai_model' => $this->blankToNull($values['ai_model'] ?? null),
        ];
    }

    private function storeLogo(UploadedFile $logo): string
    {
        // Re-encode through GD so anything beyond pure pixel data
        // (EXIF payloads, appended bytes) is stripped before storage.
        $source = @imagecreatefromstring((string) file_get_contents($logo->getRealPath()));

        if ($source === false) {
            throw ValidationException::withMessages([
                'company_logo' => __('The company logo must be a valid image file.'),
            ]);
        }

        $isPng = $logo->getMimeType() === 'image/png';
        $path = 'crm/settings/company-logo-'.Str::uuid().'.'.($isPng ? 'png' : 'jpg');

        ob_start();

        if ($isPng) {
            imagesavealpha($source, true);
            imagepng($source);
        } else {
            imagejpeg($source, null, 90);
        }

        $binary = (string) ob_get_clean();
        imagedestroy($source);

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->get($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function groupFor(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'company_'), in_array($key, ['tax_number', 'tax_office'], true) => 'company',
            str_starts_with($key, 'quote_'), str_starts_with($key, 'default_') => 'quotes',
            str_starts_with($key, 'notify_') => 'notifications',
            str_starts_with($key, 'ai_') => 'ai',
            default => 'general',
        };
    }

    private function typeFor(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'notify_'), $key === 'ai_enabled' => 'boolean',
            $key === 'default_tax_rate' => 'float',
            default => 'string',
        };
    }

    private function cacheKey(?int $organizationId = null): string
    {
        // Tenant-ready: settings are cached per organization scope. The
        // single-tenant default uses the null/default bucket.
        return 'crm_settings_'.($organizationId ?? 'default');
    }
}
