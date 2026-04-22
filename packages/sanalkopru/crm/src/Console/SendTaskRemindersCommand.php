<?php

namespace Sanalkopru\Crm\Console;

use Illuminate\Console\Command;
use Sanalkopru\Crm\Models\Task;
use Sanalkopru\Crm\Notifications\TaskReminderNotification;

class SendTaskRemindersCommand extends Command
{
    protected $signature = 'crm:tasks:send-reminders';

    protected $description = 'Send due CRM task reminder notifications.';

    public function handle(): int
    {
        $sent = 0;

        Task::query()
            ->with('assignee')
            ->incomplete()
            ->whereNotNull('assigned_to')
            ->whereNotNull('reminder_at')
            ->whereNull('reminder_notified_at')
            ->where('reminder_at', '<=', now())
            ->orderBy('reminder_at')
            ->chunkById(100, function ($tasks) use (&$sent): void {
                foreach ($tasks as $task) {
                    if (! $task->assignee) {
                        continue;
                    }

                    $task->assignee->notify(new TaskReminderNotification($task));
                    $task->forceFill(['reminder_notified_at' => now()])->save();
                    $sent++;
                }
            });

        $this->info("Sent {$sent} CRM task reminder notification(s).");

        return self::SUCCESS;
    }
}
