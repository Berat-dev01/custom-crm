<?php

namespace App\Crm\Actions\Tasks;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Events\TaskCompleted;
use App\Crm\Models\Task;

class CompleteTask
{
    public function handle(Task $task, ?Authenticatable $user = null): Task
    {
        $task->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        $task = $task->refresh();
        event(new TaskCompleted($task, $user));

        return $task;
    }
}
