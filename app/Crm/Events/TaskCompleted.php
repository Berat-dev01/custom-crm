<?php

namespace App\Crm\Events;

use App\Crm\Models\Task;
use Illuminate\Contracts\Auth\Authenticatable;

class TaskCompleted
{
    public function __construct(
        public readonly Task $task,
        public readonly ?Authenticatable $user = null
    ) {}
}
