<?php

namespace Tests\Feature;

use App\Crm\Models\Task;
use App\Crm\Notifications\TaskReminderNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CrmTaskReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_reminder_command_sends_only_due_unnotified_incomplete_tasks(): void
    {
        Notification::fake();
        $assignee = User::factory()->create();
        $dueTask = Task::factory()->create([
            'assigned_to' => $assignee->id,
            'status' => 'open',
            'reminder_at' => now()->subMinute(),
            'reminder_notified_at' => null,
        ]);
        Task::factory()->create([
            'assigned_to' => $assignee->id,
            'status' => 'open',
            'reminder_at' => now()->addHour(),
            'reminder_notified_at' => null,
        ]);
        Task::factory()->completed()->create([
            'assigned_to' => $assignee->id,
            'reminder_at' => now()->subMinute(),
            'reminder_notified_at' => null,
        ]);
        Task::factory()->create([
            'assigned_to' => $assignee->id,
            'status' => 'open',
            'reminder_at' => now()->subMinute(),
            'reminder_notified_at' => now()->subMinute(),
        ]);
        Task::factory()->create([
            'assigned_to' => null,
            'status' => 'open',
            'reminder_at' => now()->subMinute(),
            'reminder_notified_at' => null,
        ]);

        $this->artisan('crm:tasks:send-reminders')
            ->expectsOutput('Sent 1 CRM task reminder notification(s).')
            ->assertSuccessful();

        Notification::assertSentTo($assignee, TaskReminderNotification::class, function (TaskReminderNotification $notification) use ($dueTask): bool {
            return $notification->task->is($dueTask);
        });
        Notification::assertCount(1);
        $this->assertNotNull($dueTask->refresh()->reminder_notified_at);
    }

    public function test_task_reminder_command_respects_notification_setting(): void
    {
        config(['crm.notifications.task_reminders' => false]);
        Notification::fake();

        $assignee = User::factory()->create();
        Task::factory()->create([
            'assigned_to' => $assignee->id,
            'status' => 'open',
            'reminder_at' => now()->subMinute(),
            'reminder_notified_at' => null,
        ]);

        $this->artisan('crm:tasks:send-reminders')
            ->expectsOutput('Sent 0 CRM task reminder notification(s).')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}
