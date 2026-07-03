<?php

namespace App\Crm\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Crm\Support\Ai\AiDriver;

class UpdateCrmSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('crm.settings.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:160'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! ($value instanceof UploadedFile)) {
                    return;
                }
                // Verify the file truly contains a valid image (guards against
                // EXIF-payload files that pass MIME checks but fail real parsing).
                if (@getimagesize($value->getRealPath()) === false) {
                    $fail(__('The :attribute must be a valid image file.'));
                }
            }],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:80'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'tax_number' => ['nullable', 'string', 'max:80'],
            'tax_office' => ['nullable', 'string', 'max:120'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'quote_prefix' => ['required', 'string', 'max:20'],
            'quote_terms' => ['nullable', 'string', 'max:10000'],
            'notify_email_enabled' => ['nullable', 'boolean'],
            'notify_task_reminders' => ['nullable', 'boolean'],
            'notify_task_assignments' => ['nullable', 'boolean'],
            'notify_quote_status_changes' => ['nullable', 'boolean'],
            'notify_import_status_updates' => ['nullable', 'boolean'],
            'ai_enabled' => ['nullable', 'boolean'],
            'ai_driver' => ['required', 'string', Rule::in(AiDriver::values())],
            'ai_model' => ['nullable', 'string', 'max:120'],
        ];
    }
}
