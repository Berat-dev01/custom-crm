<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Quote;
use App\Crm\Models\Task;
use App\Crm\Models\CrmSetting;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Notifications\DealClosedNotification;
use App\Crm\Notifications\TaskAssignmentNotification;
use App\Crm\Notifications\TaskReminderNotification;
use App\Crm\Services\Notifications\CrmBusinessNotifier;
use App\Crm\Support\CrmLabelCatalog;
use Tests\TestCase;

class CrmNotificationsModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_notifications_endpoint_returns_items_and_unread_count(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->admin->id,
            'title' => 'Call procurement',
            'due_at' => now()->addHour(),
        ]);

        Notification::sendNow($this->admin, new TaskReminderNotification($task), ['database']);

        $this->actingAs($this->admin, 'admin')
            ->getJson(route('crm.notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('items.0.kind', 'task_reminder')
            ->assertJsonPath('items.0.title', trans('crm::notifications.task_reminder.database_title'))
            ->assertJsonPath('items.0.body', 'Call procurement')
            ->assertJsonPath('items.0.unread', true);
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->admin->id,
            'title' => 'Prepare quote',
        ]);

        Notification::sendNow($this->admin, new TaskReminderNotification($task), ['database']);
        $notificationId = $this->admin->notifications()->firstOrFail()->id;

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('crm.notifications.read', ['notification' => $notificationId]))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($this->admin->fresh()->notifications()->firstOrFail()->read_at);
    }

    public function test_all_notifications_can_be_marked_as_read(): void
    {
        $first = Task::factory()->create(['assigned_to' => $this->admin->id, 'title' => 'First reminder']);
        $second = Task::factory()->create(['assigned_to' => $this->admin->id, 'title' => 'Second reminder']);

        Notification::sendNow($this->admin, new TaskReminderNotification($first), ['database']);
        Notification::sendNow($this->admin, new TaskReminderNotification($second), ['database']);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('crm.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $this->admin->fresh()->unreadNotifications()->count());
    }

    public function test_notifications_page_renders_latest_items(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->admin->id,
            'title' => 'Page reminder',
        ]);

        Notification::sendNow($this->admin, new TaskReminderNotification($task), ['database']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.notifications.page'))
            ->assertOk()
            ->assertSee(__('Notifications'))
            ->assertSee('Page reminder')
            ->assertSee(__('Mark all as read'));
    }

    public function test_duplicate_unread_notifications_are_suppressed(): void
    {
        $quote = Quote::factory()->create(['owner_id' => $this->admin->id, 'status' => 'draft']);

        $notifier = app(CrmBusinessNotifier::class);
        $notifier->quoteStatusChanged($quote, 'sent');
        $notifier->quoteStatusChanged($quote, 'sent');

        $this->assertSame(1, $this->admin->fresh()->unreadNotifications()->count());
        $notification = $this->admin->fresh()->unreadNotifications()->first();

        $this->assertSame('quote_sent', data_get($notification?->data, 'kind'));
        $this->assertSame(
            trans('crm::notifications.quote_status_changed.title', [
                'status' => app(CrmLabelCatalog::class)->status('sent'),
            ]),
            data_get($notification?->data, 'title')
        );
    }

    public function test_email_channel_follows_global_and_user_preferences(): void
    {
        Notification::fake();

        $assignee = User::factory()->create()->assignRole('crm_sales');
        $task = Task::factory()->create(['assigned_to' => $assignee->id]);

        // Default: global switch on, no user opt-out -> mail included.
        app(CrmBusinessNotifier::class)->taskAssigned($task->fresh('assignee'), $this->admin);
        Notification::assertSentTo(
            $assignee,
            TaskAssignmentNotification::class,
            fn (TaskAssignmentNotification $notification, array $channels): bool => in_array('mail', $channels, true)
        );
    }

    public function test_email_channel_respects_user_opt_out(): void
    {
        Notification::fake();

        $assignee = User::factory()->create()->assignRole('crm_sales');
        $assignee->forceFill(['notification_email_prefs' => ['task_assignments' => false]])->save();
        $task = Task::factory()->create(['assigned_to' => $assignee->id]);

        app(CrmBusinessNotifier::class)->taskAssigned($task->fresh('assignee'), $this->admin);

        Notification::assertSentTo(
            $assignee,
            TaskAssignmentNotification::class,
            fn (TaskAssignmentNotification $notification, array $channels): bool => ! in_array('mail', $channels, true)
                && in_array('database', $channels, true)
        );
    }

    public function test_email_channel_respects_global_switch(): void
    {
        Notification::fake();

        CrmSetting::query()->create([
            'organization_id' => null,
            'key' => 'notify_email_enabled',
            'group' => 'notifications',
            'value' => ['value' => false],
            'type' => 'boolean',
            'is_encrypted' => false,
        ]);
        \Illuminate\Support\Facades\Cache::flush();

        $assignee = User::factory()->create()->assignRole('crm_sales');
        $task = Task::factory()->create(['assigned_to' => $assignee->id]);

        app(CrmBusinessNotifier::class)->taskAssigned($task->fresh('assignee'), $this->admin);

        Notification::assertSentTo(
            $assignee,
            TaskAssignmentNotification::class,
            fn (TaskAssignmentNotification $notification, array $channels): bool => ! in_array('mail', $channels, true)
        );
    }

    public function test_email_preferences_can_be_saved_from_notifications_page(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.notifications.preferences'), [
                'email_prefs' => [
                    'task_reminders' => '1',
                    'task_assignments' => '0',
                    'quote_status_changes' => '1',
                    'import_status_updates' => '0',
                ],
            ])
            ->assertRedirect();

        $prefs = $this->admin->refresh()->notification_email_prefs;
        $this->assertTrue($prefs['task_reminders']);
        $this->assertFalse($prefs['task_assignments']);
        $this->assertTrue($prefs['quote_status_changes']);
        $this->assertFalse($prefs['import_status_updates']);
    }

    public function test_notification_mail_message_reuses_database_payload(): void
    {
        $task = Task::factory()->create(['assigned_to' => $this->admin->id]);

        $mail = (new TaskAssignmentNotification($task))->toMail($this->admin);

        $this->assertNotEmpty($mail->subject);
        $this->assertStringContainsString((string) route('crm.tasks.show', $task), (string) $mail->actionUrl);
    }

    public function test_deal_owner_is_notified_when_deal_is_won_or_lost(): void
    {
        Notification::fake();
        $this->seed(\App\Crm\Database\Seeders\CrmDealStageSeeder::class);

        $owner = User::factory()->create()->assignRole('crm_sales');
        $openStage = DealStage::query()->where('is_won', false)->where('is_lost', false)->ordered()->firstOrFail();
        $deal = Deal::factory()->create(['stage_id' => $openStage->id, 'status' => 'open', 'owner_id' => $owner->id]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.deals.close-won', $deal))
            ->assertRedirect();

        Notification::assertSentTo(
            $owner,
            DealClosedNotification::class,
            fn (DealClosedNotification $notification, array $channels): bool => $notification->result === 'won'
                && in_array('mail', $channels, true)
        );
    }

    public function test_deal_close_actor_does_not_notify_themselves(): void
    {
        Notification::fake();
        $this->seed(\App\Crm\Database\Seeders\CrmDealStageSeeder::class);

        $openStage = DealStage::query()->where('is_won', false)->where('is_lost', false)->ordered()->firstOrFail();
        $deal = Deal::factory()->create(['stage_id' => $openStage->id, 'status' => 'open', 'owner_id' => $this->admin->id]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.deals.close-won', $deal))
            ->assertRedirect();

        Notification::assertNotSentTo($this->admin, DealClosedNotification::class);
    }
}
