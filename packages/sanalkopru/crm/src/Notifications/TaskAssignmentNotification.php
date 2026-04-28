<?php

namespace Sanalkopru\Crm\Notifications;

use Illuminate\Notifications\Notification;
use Sanalkopru\Crm\Models\Task;

class TaskAssignmentNotification extends Notification
{
    public function __construct(
        public readonly Task $task,
        public readonly bool $reassigned = false
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->reassigned ? 'task_reassigned' : 'task_assigned',
            'task_id' => $this->task->id,
            'title' => $this->reassigned
                ? trans('crm::notifications.task_assignment.reassigned_title')
                : trans('crm::notifications.task_assignment.assigned_title'),
            'body' => $this->task->title,
            'priority' => $this->task->priority,
            'due_at' => $this->task->due_at?->toISOString(),
            'url' => route('crm.tasks.show', $this->task),
        ];
    }
}
