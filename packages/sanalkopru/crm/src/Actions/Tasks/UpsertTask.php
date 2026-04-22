<?php

namespace Sanalkopru\Crm\Actions\Tasks;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Task;

class UpsertTask
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Task $task, array $payload, ?Authenticatable $user = null): Task
    {
        $payload[$task->exists ? 'updated_by' : 'created_by'] = $user?->getAuthIdentifier();

        if (($payload['status'] ?? $task->status) === 'completed' && ! $task->completed_at) {
            $payload['completed_at'] = now();
        }

        if (($payload['status'] ?? $task->status) !== 'completed') {
            $payload['completed_at'] = null;
        }

        if (($payload['reminder_at'] ?? null) !== $task->reminder_at?->format('Y-m-d H:i:s')) {
            $payload['reminder_notified_at'] = null;
        }

        $task->fill($payload);
        $task->save();

        return $task->refresh();
    }
}
