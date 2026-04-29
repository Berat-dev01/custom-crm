<?php

namespace App\Crm\Listeners;

use App\Crm\Events\TaskCompleted;
use App\Crm\Services\Activities\ActivityLogger;

class LogTaskCompletedActivity
{
    public function __construct(private readonly ActivityLogger $activities) {}

    public function handle(TaskCompleted $event): void
    {
        $activityable = $event->task->taskable;

        if (! $activityable) {
            return;
        }

        $this->activities->system(
            $activityable,
            'Task completed',
            'task_completed',
            $event->user,
            $event->task->title,
            ['task_id' => $event->task->id]
        );
    }
}
