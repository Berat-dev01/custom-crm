<?php

namespace App\Crm\Actions\Tasks;

use App\Crm\Models\Task;
use App\Crm\Services\Notifications\CrmBusinessNotifier;
use Illuminate\Contracts\Auth\Authenticatable;

class UpsertTask
{
    public function __construct(private readonly CrmBusinessNotifier $notifications) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Task $task, array $payload, ?Authenticatable $user = null): Task
    {
        $previousAssignee = $task->assigned_to;
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

        $task = $task->refresh();

        if ($task->assigned_to && (int) $task->assigned_to !== (int) $previousAssignee) {
            $this->notifications->taskAssigned($task, $user, $previousAssignee !== null);
        }

        return $task;
    }
}
