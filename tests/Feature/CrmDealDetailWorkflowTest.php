<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Activity;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use App\Crm\Models\QuoteItem;
use App\Crm\Models\Task as CrmTask;
use Tests\TestCase;

class CrmDealDetailWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_deal_show_is_sales_workspace_with_filtered_timeline(): void
    {
        $stage = DealStage::factory()->create(['name' => 'Proposal', 'slug' => 'proposal', 'is_won' => false, 'is_lost' => false]);
        $deal = Deal::factory()->create(['stage_id' => $stage->id, 'owner_id' => $this->admin->id, 'title' => 'Workspace Deal']);
        CrmTask::factory()->create([
            'taskable_type' => $deal::class,
            'taskable_id' => $deal->id,
            'title' => 'Next follow up',
            'due_at' => now()->addDay(),
            'completed_at' => null,
        ]);
        Activity::factory()->create([
            'activityable_type' => $deal::class,
            'activityable_id' => $deal->id,
            'type' => 'note',
            'subject' => 'Important note',
            'occurred_at' => now(),
        ]);
        Activity::factory()->create([
            'activityable_type' => $deal::class,
            'activityable_id' => $deal->id,
            'type' => 'call',
            'subject' => 'Discovery call',
            'occurred_at' => now()->subHour(),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.deals.show', ['deal' => $deal, 'activity_type' => 'note']))
            ->assertOk()
            ->assertSee(__('Change Stage'))
            ->assertSee(__('Next Task'))
            ->assertSee('Next follow up')
            ->assertSee(__('Add Task'))
            ->assertSee(__('Create Quote'))
            ->assertSee(__('Add Activity'))
            ->assertSee(__('AI Email Draft'))
            ->assertSee('Important note')
            ->assertDontSee('Discovery call');
    }

    public function test_deal_detail_can_create_related_task_quote_and_activity(): void
    {
        $stage = DealStage::factory()->create(['name' => 'New', 'slug' => 'new', 'is_won' => false, 'is_lost' => false]);
        $deal = Deal::factory()->create([
            'stage_id' => $stage->id,
            'owner_id' => $this->admin->id,
            'currency' => 'TRY',
            'value' => 10000,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.deals.tasks.store', $deal), [
                'title' => 'Prepare ROI note',
                'description' => 'Build a short business case.',
                'assigned_to' => $this->admin->id,
                'due_at' => '2026-05-01 10:00:00',
                'priority' => 'high',
            ])
            ->assertRedirect(route('crm.deals.show', $deal));

        $this->assertDatabaseHas('tasks', [
            'taskable_type' => $deal::class,
            'taskable_id' => $deal->id,
            'title' => 'Prepare ROI note',
            'priority' => 'high',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.deals.quotes.store', $deal), [
                'item_name' => 'CRM Setup',
                'quantity' => 2,
                'unit_price' => 1000,
                'tax_rate' => 20,
                'currency' => 'TRY',
                'valid_until' => '2026-05-30',
                'notes' => 'Introductory offer',
            ])
            ->assertRedirect(route('crm.deals.show', $deal));

        $quote = Quote::query()->where('deal_id', $deal->id)->firstOrFail();
        $this->assertSame('2400.00', $quote->grand_total);
        $this->assertSame(1, QuoteItem::query()->where('quote_id', $quote->id)->count());

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.deals.activities.store', $deal), [
                'type' => 'meeting',
                'subject' => 'Pricing meeting',
                'body' => 'Discussed procurement timeline.',
                'occurred_at' => '2026-05-02 11:00:00',
            ])
            ->assertRedirect(route('crm.deals.show', $deal));

        $this->assertDatabaseHas('activities', [
            'activityable_type' => $deal::class,
            'activityable_id' => $deal->id,
            'type' => 'meeting',
            'subject' => 'Pricing meeting',
        ]);
    }

    public function test_deal_detail_stage_and_close_actions_update_status(): void
    {
        $open = DealStage::factory()->create(['name' => 'Open', 'slug' => 'open', 'position' => 1, 'probability' => 25, 'is_won' => false, 'is_lost' => false]);
        $proposal = DealStage::factory()->create(['name' => 'Proposal', 'slug' => 'proposal', 'position' => 2, 'probability' => 60, 'is_won' => false, 'is_lost' => false]);
        $won = DealStage::factory()->won()->create(['position' => 3]);
        $lost = DealStage::factory()->lost()->create(['position' => 4]);
        $deal = Deal::factory()->create(['stage_id' => $open->id, 'status' => 'open', 'probability' => 25]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.deals.stage', $deal), [
                'stage_id' => $proposal->id,
            ])
            ->assertRedirect(route('crm.deals.show', $deal));

        $deal->refresh();
        $this->assertSame($proposal->id, $deal->stage_id);
        $this->assertSame(60, $deal->probability);
        $this->assertSame('open', $deal->status);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.deals.close-won', $deal))
            ->assertRedirect(route('crm.deals.show', $deal));

        $deal->refresh();
        $this->assertSame($won->id, $deal->stage_id);
        $this->assertSame('won', $deal->status);
        $this->assertNotNull($deal->closed_at);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.deals.close-lost', $deal), [
                'lost_reason' => 'No budget',
            ])
            ->assertRedirect(route('crm.deals.show', $deal));

        $deal->refresh();
        $this->assertSame($lost->id, $deal->stage_id);
        $this->assertSame('lost', $deal->status);
        $this->assertSame('No budget', $deal->lost_reason);
    }

    public function test_related_deal_actions_are_policy_protected(): void
    {
        $stage = DealStage::factory()->create(['name' => 'New', 'slug' => 'new', 'is_won' => false, 'is_lost' => false]);
        $deal = Deal::factory()->create(['stage_id' => $stage->id]);
        $viewer = User::factory()->create()->assignRole('crm_viewer');

        $this->actingAs($viewer, 'admin')
            ->post(route('crm.deals.tasks.store', $deal), [
                'title' => 'Unauthorized task',
                'priority' => 'normal',
            ])
            ->assertForbidden();

        $this->actingAs($viewer, 'admin')
            ->post(route('crm.deals.activities.store', $deal), [
                'type' => 'note',
                'subject' => 'Unauthorized note',
            ])
            ->assertForbidden();

        $this->actingAs($viewer, 'admin')
            ->patch(route('crm.deals.close-won', $deal))
            ->assertForbidden();
    }
}
