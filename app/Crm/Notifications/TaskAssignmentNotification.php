<?php

namespace App\Crm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Crm\Notifications\Concerns\RoutesEmailByPreference;
use App\Crm\Models\Task;

class TaskAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesEmailByPreference;

    public const EMAIL_PREFERENCE_KEY = 'task_assignments';

    public function __construct(
        public readonly Task $task,
        public readonly bool $reassigned = false
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($data['title'])
            ->greeting(trans('crm::notifications.mail.greeting', ['name' => $notifiable->name ?? '']))
            ->line($data['body'])
            ->action(trans('crm::notifications.mail.action'), $data['url'])
            ->line(trans('crm::notifications.mail.footer'));
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
