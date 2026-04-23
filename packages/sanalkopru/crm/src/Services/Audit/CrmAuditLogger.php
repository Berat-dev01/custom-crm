<?php

namespace Sanalkopru\Crm\Services\Audit;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Sanalkopru\Crm\Models\CrmAuditLog;

class CrmAuditLogger
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $event,
        ?Model $auditable = null,
        ?Authenticatable $user = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $metadata = []
    ): CrmAuditLog {
        return CrmAuditLog::query()->create([
            'organization_id' => $this->organizationId($auditable),
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'user_id' => $user?->getAuthIdentifier(),
            'old_values' => $oldValues === null ? null : $this->sanitize($oldValues),
            'new_values' => $newValues === null ? null : $this->sanitize($newValues),
            'metadata' => $this->sanitize($metadata),
            'ip_address' => Request::ip(),
            'user_agent' => Str::limit((string) Request::userAgent(), 1000, ''),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array{old: array<string, mixed>, new: array<string, mixed>}
     */
    public function diff(array $before, array $after): array
    {
        $old = [];
        $new = [];

        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) === $value) {
                continue;
            }

            $old[$key] = $before[$key] ?? null;
            $new[$key] = $value;
        }

        return [
            'old' => $old,
            'new' => $new,
        ];
    }

    private function organizationId(?Model $auditable): mixed
    {
        return $auditable && $auditable->getAttribute('organization_id')
            ? $auditable->getAttribute('organization_id')
            : null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        return collect($values)
            ->mapWithKeys(function (mixed $value, string|int $key): array {
                $key = (string) $key;

                if ($this->isSensitiveKey($key)) {
                    return [$key => '[redacted]'];
                }

                if (is_array($value)) {
                    return [$key => $this->sanitize($value)];
                }

                if (is_string($value)) {
                    return [$key => Str::limit($value, 500, '...')];
                }

                return [$key => $value];
            })
            ->all();
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = Str::lower($key);

        return Str::contains($key, [
            'password',
            'token',
            'secret',
            'api_key',
            'authorization',
            'cookie',
            'email',
            'phone',
            'address',
            'notes',
            'reason',
            'body',
            'content',
            'description',
        ]);
    }
}
