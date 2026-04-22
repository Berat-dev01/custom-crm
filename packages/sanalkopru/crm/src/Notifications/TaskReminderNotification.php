<?php

namespace Sanalkopru\Crm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Sanalkopru\Crm\Models\Task;

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
        return (new MailMessage)
            ->subject('CRM Task Reminder: '.$this->task->title)
            ->line('A CRM task reminder is due.')
            ->line($this->task->title)
            ->line('Due at: '.($this->task->due_at?->format('Y-m-d H:i') ?: 'No due date'))
            ->action('Open Tasks', route('crm.tasks.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'due_at' => $this->task->due_at?->toISOString(),
            'priority' => $this->task->priority,
            'url' => route('crm.tasks.show', $this->task),
        ];
    }
}
