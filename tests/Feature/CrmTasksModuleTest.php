<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Task as CrmTask;
use Sanalkopru\Crm\Notifications\TaskAssignmentNotification;
use Sanalkopru\Crm\Notifications\TaskReminderNotification;
use Tests\TestCase;

class CrmTasksModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_task_indexes_filter_my_today_and_overdue_tasks(): void
    {
        $this->travelTo(today()->setHour(9));

        $myToday = CrmTask::factory()->create([
            'title' => 'My today task',
            'assigned_to' => $this->admin->id,
            'due_at' => today()->setHour(14),
            'status' => 'open',
            'completed_at' => null,
        ]);
        CrmTask::factory()->create([
            'title' => 'Other today task',
            'assigned_to' => User::factory(),
            'due_at' => today()->setHour(16),
            'status' => 'open',
            'completed_at' => null,
        ]);
        CrmTask::factory()->create([
            'title' => 'Very overdue task',
            'assigned_to' => $this->admin->id,
            'due_at' => now()->subDay(),
            'status' => 'open',
            'completed_at' => null,
        ]);
        CrmTask::factory()->create([
            'title' => 'Future task',
            'assigned_to' => $this->admin->id,
            'due_at' => now()->addWeek(),
            'status' => 'open',
            'completed_at' => null,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.tasks.my'))
            ->assertOk()
            ->assertSee('My today task')
            ->assertDontSee('Other today task');

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.tasks.today'))
            ->assertOk()
            ->assertSee('My today task')
            ->assertSee('Other today task')
            ->assertDontSee('Future task');

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.tasks.overdue'))
            ->assertOk()
            ->assertSee('Very overdue task')
            ->assertDontSee($myToday->title);
    }

    public function test_task_can_be_created_updated_completed_and_deleted(): void
    {
        $company = Company::factory()->create(['name' => 'Task Company']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('crm.tasks.store'), [
                'title' => 'Call decision maker',
                'description' => 'Confirm next steps.',
                'taskable_type' => 'company',
                'taskable_id' => $company->id,
                'assigned_to' => $this->admin->id,
                'due_at' => '2026-05-01 09:00:00',
                'reminder_at' => '2026-05-01 08:30:00',
                'priority' => 'urgent',
                'status' => 'open',
            ]);

        $task = CrmTask::query()->where('title', 'Call decision maker')->firstOrFail();

        $response->assertRedirect(route('crm.tasks.show', $task));
        $this->assertSame($company->id, $task->taskable_id);
        $this->assertSame($company::class, $task->taskable_type);

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.tasks.update', $task), [
                'title' => 'Call CFO',
                'description' => 'Confirm procurement steps.',
                'taskable_type' => 'company',
                'taskable_id' => $company->id,
                'assigned_to' => $this->admin->id,
                'due_at' => '2026-05-02 09:00:00',
                'reminder_at' => '2026-05-02 08:30:00',
                'priority' => 'high',
                'status' => 'in_progress',
            ])
            ->assertRedirect(route('crm.tasks.show', $task));

        $task->refresh();
        $this->assertSame('Call CFO', $task->title);
        $this->assertSame('in_progress', $task->status);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.tasks.complete', $task))
            ->assertRedirect();

        $this->assertSame('completed', $task->refresh()->status);
        $this->assertNotNull($task->completed_at);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.tasks.destroy', $task))
            ->assertRedirect(route('crm.tasks.index'));

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_task_show_can_display_related_deal_context(): void
    {
        $stage = DealStage::factory()->create(['name' => 'Open', 'slug' => 'open', 'is_won' => false, 'is_lost' => false]);
        $deal = Deal::factory()->create(['title' => 'Related Deal', 'stage_id' => $stage->id]);
        $task = CrmTask::factory()->create([
            'title' => 'Deal follow up',
            'taskable_type' => $deal::class,
            'taskable_id' => $deal->id,
            'assigned_to' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.tasks.show', $task))
            ->assertOk()
            ->assertSee('Deal follow up')
            ->assertSee('Related Deal');
    }

    public function test_task_assignment_notifies_new_assignee_and_reassignment(): void
    {
        Notification::fake();

        $company = Company::factory()->create(['name' => 'Notify Co']);
        $firstAssignee = User::factory()->create();
        $secondAssignee = User::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.tasks.store'), [
                'title' => 'Assigned by manager',
                'description' => 'Owner should see this in bell.',
                'taskable_type' => 'company',
                'taskable_id' => $company->id,
                'assigned_to' => $firstAssignee->id,
                'due_at' => '2026-05-01 09:00:00',
                'priority' => 'high',
                'status' => 'open',
            ])
            ->assertRedirect();

        $task = CrmTask::query()->where('title', 'Assigned by manager')->firstOrFail();

        Notification::assertSentTo(
            $firstAssignee,
            TaskAssignmentNotification::class,
            fn (TaskAssignmentNotification $notification): bool => $notification->task->is($task) && ! $notification->reassigned
        );

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.tasks.update', $task), [
                'title' => 'Assigned by manager',
                'description' => 'Owner should see this in bell.',
                'taskable_type' => 'company',
                'taskable_id' => $company->id,
                'assigned_to' => $secondAssignee->id,
                'due_at' => '2026-05-02 09:00:00',
                'priority' => 'urgent',
                'status' => 'in_progress',
            ])
            ->assertRedirect(route('crm.tasks.show', $task));

        Notification::assertSentTo(
            $secondAssignee,
            TaskAssignmentNotification::class,
            fn (TaskAssignmentNotification $notification): bool => $notification->task->is($task->fresh()) && $notification->reassigned
        );
    }

    public function test_task_assignment_notifications_can_be_disabled_from_settings_defaults(): void
    {
        Notification::fake();
        config(['crm.notifications.task_reminders' => true]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.settings.update'), [
                'company_name' => 'Task Prefs Co',
                'default_currency' => 'TRY',
                'default_tax_rate' => '20',
                'quote_prefix' => 'CRM-',
                'quote_terms' => '',
                'notify_task_reminders' => '1',
                'notify_task_assignments' => '0',
                'notify_quote_status_changes' => '1',
                'notify_import_status_updates' => '1',
                'ai_enabled' => '0',
                'ai_driver' => 'openai',
                'ai_model' => '',
            ])
            ->assertRedirect(route('crm.settings.index'));

        $company = Company::factory()->create();
        $assignee = User::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.tasks.store'), [
                'title' => 'Silent assignment',
                'taskable_type' => 'company',
                'taskable_id' => $company->id,
                'assigned_to' => $assignee->id,
                'due_at' => '2026-05-01 09:00:00',
                'priority' => 'normal',
                'status' => 'open',
            ])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_due_task_reminders_send_queued_notifications_once(): void
    {
        Notification::fake();

        $assignee = User::factory()->create();
        $task = CrmTask::factory()->create([
            'title' => 'Reminder task',
            'assigned_to' => $assignee->id,
            'reminder_at' => now()->subMinute(),
            'reminder_notified_at' => null,
            'status' => 'open',
            'completed_at' => null,
        ]);
        CrmTask::factory()->completed()->create([
            'assigned_to' => $assignee->id,
            'reminder_at' => now()->subMinute(),
            'reminder_notified_at' => null,
        ]);

        $notificationProbe = new TaskReminderNotification($task);
        $this->assertInstanceOf(ShouldQueue::class, $notificationProbe);
        $this->assertSame(['database', 'mail'], $notificationProbe->via($assignee));

        $this->artisan('crm:tasks:send-reminders')->assertSuccessful();

        Notification::assertSentTo(
            $assignee,
            TaskReminderNotification::class,
            fn (TaskReminderNotification $notification): bool => $notification->task->is($task)
        );

        $this->assertNotNull($task->refresh()->reminder_notified_at);

        Notification::fake();
        $this->artisan('crm:tasks:send-reminders')->assertSuccessful();
        Notification::assertNothingSent();
    }

    public function test_task_actions_are_policy_protected(): void
    {
        $viewer = User::factory()->create()->assignRole('crm_viewer');
        $task = CrmTask::factory()->create(['assigned_to' => $this->admin->id]);

        $this->actingAs($viewer, 'admin')
            ->post(route('crm.tasks.store'), [
                'title' => 'Forbidden',
                'priority' => 'normal',
                'status' => 'open',
            ])
            ->assertForbidden();

        $this->actingAs($viewer, 'admin')
            ->patch(route('crm.tasks.complete', $task))
            ->assertForbidden();
    }

    public function test_tasks_can_be_bulk_deleted(): void
    {
        $first = CrmTask::factory()->create();
        $second = CrmTask::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.tasks.bulk-delete'), [
                'record_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('tasks', ['id' => $first->id]);
        $this->assertSoftDeleted('tasks', ['id' => $second->id]);
    }
}
