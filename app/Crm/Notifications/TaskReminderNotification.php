<?php

namespace App\Crm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Crm\Models\Task;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Task $task) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueAt = $this->task->due_at?->format('Y-m-d H:i')
            ?: trans('crm::notifications.task_reminder.mail_no_due_date');

        return (new MailMessage)
            ->subject(trans('crm::notifications.task_reminder.mail_subject', ['task' => $this->task->title]))
            ->line(trans('crm::notifications.task_reminder.mail_intro'))
            ->line($this->task->title)
            ->line(trans('crm::notifications.task_reminder.mail_due_at', ['value' => $dueAt]))
            ->action(trans('crm::notifications.task_reminder.mail_action'), route('crm.tasks.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'task_reminder',
            'task_id' => $this->task->id,
            'title' => trans('crm::notifications.task_reminder.database_title'),
            'body' => $this->task->title,
            'due_at' => $this->task->due_at?->toISOString(),
            'priority' => $this->task->priority,
            'url' => route('crm.tasks.show', $this->task),
        ];
    }
}
