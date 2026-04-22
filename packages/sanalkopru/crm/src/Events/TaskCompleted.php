<?php

namespace Sanalkopru\Crm\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Task;

class TaskCompleted
{
    public function __construct(
        public readonly Task $task,
        public readonly ?Authenticatable $user = null
    ) {}
}
