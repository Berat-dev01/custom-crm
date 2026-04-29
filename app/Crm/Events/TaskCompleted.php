<?php

namespace App\Crm\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\Task;

class TaskCompleted
{
    public function __construct(
        public readonly Task $task,
        public readonly ?Authenticatable $user = null
    ) {}
}
