<?php

namespace App\Crm\Actions\Tasks;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Events\TaskCompleted;
use App\Crm\Models\Task;
use App\Crm\Services\Webhooks\CrmWebhookDispatcher;

class CompleteTask
{
    public function __construct(private readonly CrmWebhookDispatcher $webhooks) {}

    public function handle(Task $task, ?Authenticatable $user = null): Task
    {
        $wasCompleted = $task->status === 'completed';

        $task->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        $task = $task->refresh();
        event(new TaskCompleted($task, $user));

        if (! $wasCompleted) {
            $this->webhooks->dispatch('task.completed', $task);
        }

        return $task;
    }
}
