<?php

namespace Sanalkopru\Crm\Actions\Tasks;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Task;

class CompleteTask
{
    public function handle(Task $task, ?Authenticatable $user = null): Task
    {
        $task->forceFill([
            'status' => 'completed',
            'completed_at' => now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        return $task->refresh();
    }
}
