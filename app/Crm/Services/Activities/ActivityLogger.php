<?php

namespace App\Crm\Services\Activities;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use App\Crm\Models\Activity;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function manual(Model $activityable, array $payload, ?Authenticatable $user = null): Activity
    {
        return $this->create($activityable, [
            ...$payload,
            'user_id' => $payload['user_id'] ?? $user?->getAuthIdentifier(),
            'created_by' => $user?->getAuthIdentifier(),
            'occurred_at' => $payload['occurred_at'] ?? now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function system(
        Model $activityable,
        string $subject,
        string $type = 'system',
        ?Authenticatable $user = null,
        ?string $body = null,
        array $metadata = []
    ): Activity {
        return $this->create($activityable, [
            'subject' => $subject,
            'body' => $body,
            'type' => $type,
            'user_id' => $user?->getAuthIdentifier(),
            'created_by' => $user?->getAuthIdentifier(),
            'occurred_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function create(Model $activityable, array $payload): Activity
    {
        $payload['subject'] = $this->sanitize($payload['subject'] ?? 'Activity');
        $payload['body'] = isset($payload['body']) ? $this->sanitize($payload['body']) : null;
        $payload['activityable_type'] = $activityable::class;
        $payload['activityable_id'] = $activityable->getKey();

        return Activity::query()->create($payload);
    }

    private function sanitize(string $value): string
    {
        return trim(strip_tags($value));
    }
}
