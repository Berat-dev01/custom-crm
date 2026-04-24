<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\Task;
use Sanalkopru\Crm\Notifications\TaskReminderNotification;
use Sanalkopru\Crm\Services\Notifications\CrmBusinessNotifier;
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
            ->assertJsonPath('items.0.title', 'Task reminder')
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

    public function test_duplicate_unread_notifications_are_suppressed(): void
    {
        $quote = Quote::factory()->create(['owner_id' => $this->admin->id, 'status' => 'draft']);

        $notifier = app(CrmBusinessNotifier::class);
        $notifier->quoteStatusChanged($quote, 'sent');
        $notifier->quoteStatusChanged($quote, 'sent');

        $this->assertSame(1, $this->admin->fresh()->unreadNotifications()->count());
        $this->assertSame('quote_sent', data_get($this->admin->fresh()->unreadNotifications()->first()?->data, 'kind'));
    }
}
