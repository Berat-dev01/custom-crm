<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Events\QuoteSent;
use App\Crm\Models\Activity;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use App\Crm\Models\Task as CrmTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmActivitiesModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_activities_index_filters_timeline_records(): void
    {
        $company = Company::factory()->create(['name' => 'Filtered Company']);
        Activity::factory()->create([
            'activityable_type' => $company->getMorphClass(),
            'activityable_id' => $company->id,
            'type' => 'call',
            'subject' => 'Procurement call',
            'user_id' => $this->admin->id,
            'occurred_at' => '2026-05-10 10:00:00',
        ]);
        Activity::factory()->create([
            'activityable_type' => (new Contact)->getMorphClass(),
            'activityable_id' => Contact::factory()->create()->id,
            'type' => 'email',
            'subject' => 'Unrelated email',
            'occurred_at' => '2026-06-01 10:00:00',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.activities.index', [
                'search' => 'Procurement',
                'type' => 'call',
                'activityable_type' => 'company',
                'user_id' => $this->admin->id,
                'occurred_from' => '2026-05-01',
                'occurred_to' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertSee('Procurement call')
            ->assertSee('Filtered Company')
            ->assertDontSee('Unrelated email');
    }

    public function test_manual_activity_can_be_created_updated_and_deleted_safely(): void
    {
        $deal = Deal::factory()->create(['stage_id' => DealStage::factory()->create(['is_won' => false, 'is_lost' => false])->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('crm.activities.store'), [
                'activityable_type' => 'deal',
                'activityable_id' => $deal->id,
                'type' => 'note',
                'subject' => '<b>Safe note</b><script>alert(1)</script>',
                'body' => '<p>Plain body</p><img src=x onerror=alert(1)>',
                'occurred_at' => '2026-05-03 12:00:00',
            ]);

        $activity = Activity::query()->where('activityable_id', $deal->id)->firstOrFail();

        $response->assertRedirect(route('crm.activities.show', $activity));
        $this->assertSame('Safe notealert(1)', $activity->subject);
        $this->assertSame('Plain body', $activity->body);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.activities.show', $activity))
            ->assertOk()
            ->assertSee('Safe notealert(1)')
            ->assertDontSee('<script>alert', false)
            ->assertDontSee('onerror', false);

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.activities.update', $activity), [
                'type' => 'meeting',
                'subject' => '<i>Updated meeting</i>',
                'body' => '<strong>Discussed close plan</strong>',
                'occurred_at' => '2026-05-04 12:00:00',
            ])
            ->assertRedirect(route('crm.activities.show', $activity));

        $activity->refresh();
        $this->assertSame('Updated meeting', $activity->subject);
        $this->assertSame('Discussed close plan', $activity->body);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.activities.destroy', $activity))
            ->assertRedirect(route('crm.activities.index'));

        $this->assertSoftDeleted('activities', ['id' => $activity->id]);
    }

    public function test_system_activities_are_logged_for_core_events(): void
    {
        $stageA = DealStage::factory()->create(['name' => 'New', 'slug' => 'new', 'position' => 1, 'is_won' => false, 'is_lost' => false]);
        $stageB = DealStage::factory()->create(['name' => 'Proposal', 'slug' => 'proposal', 'position' => 2, 'is_won' => false, 'is_lost' => false]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.contacts.store'), [
                'full_name' => 'Event Contact',
                'lifecycle_stage' => 'lead',
            ])
            ->assertRedirect();

        $contact = Contact::query()->where('full_name', 'Event Contact')->firstOrFail();
        $this->assertDatabaseHas('activities', [
            'activityable_type' => $contact->getMorphClass(),
            'activityable_id' => $contact->id,
            'type' => 'system',
            'subject' => 'Contact created',
        ]);

        $deal = Deal::factory()->create(['stage_id' => $stageA->id]);
        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('crm.deals.move', $deal), [
                'stage_id' => $stageB->id,
                'position' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('activities', [
            'activityable_type' => $deal->getMorphClass(),
            'activityable_id' => $deal->id,
            'type' => 'deal_moved',
            'subject' => 'Deal moved',
        ]);

        $task = CrmTask::factory()->create([
            'taskable_type' => $deal->getMorphClass(),
            'taskable_id' => $deal->id,
            'status' => 'open',
            'completed_at' => null,
        ]);
        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.tasks.complete', $task))
            ->assertRedirect();

        $this->assertDatabaseHas('activities', [
            'activityable_type' => $deal->getMorphClass(),
            'activityable_id' => $deal->id,
            'type' => 'task_completed',
            'subject' => 'Task completed',
        ]);

        $quote = Quote::factory()->create(['deal_id' => $deal->id]);
        event(new QuoteSent($quote->load('deal'), $this->admin));

        $this->assertDatabaseHas('activities', [
            'activityable_type' => $deal->getMorphClass(),
            'activityable_id' => $deal->id,
            'type' => 'quote_sent',
            'subject' => 'Quote sent',
        ]);
    }

    public function test_activity_actions_are_policy_protected(): void
    {
        $viewer = User::factory()->create()->assignRole('crm_viewer');
        $deal = Deal::factory()->create(['stage_id' => DealStage::factory()->create(['is_won' => false, 'is_lost' => false])->id]);
        $activity = Activity::factory()->create([
            'activityable_type' => $deal->getMorphClass(),
            'activityable_id' => $deal->id,
        ]);

        $this->actingAs($viewer, 'admin')
            ->post(route('crm.activities.store'), [
                'activityable_type' => 'deal',
                'activityable_id' => $deal->id,
                'type' => 'note',
                'subject' => 'Forbidden',
            ])
            ->assertForbidden();

        $this->actingAs($viewer, 'admin')
            ->put(route('crm.activities.update', $activity), [
                'type' => 'note',
                'subject' => 'Forbidden update',
            ])
            ->assertForbidden();
    }

    public function test_activities_index_uses_admin_panel_pagination_markup(): void
    {
        $contact = Contact::factory()->create();

        Activity::factory()
            ->count(30)
            ->sequence(fn ($sequence) => [
                'activityable_type' => $contact->getMorphClass(),
                'activityable_id' => $contact->id,
                'user_id' => $this->admin->id,
                'subject' => 'Paged Activity #'.str_pad((string) ($sequence->index + 1), 3, '0', STR_PAD_LEFT),
                'occurred_at' => now()->subMinutes($sequence->index),
            ])
            ->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.activities.index'))
            ->assertOk()
            ->assertSee('crm-pagination', false)
            ->assertSee('pagination-wrapper', false)
            ->assertSee('pagination-wrapper-compact', false)
            ->assertSee('name="per_page"', false)
            ->assertSee('Rows')
            ->assertSee('class="pagination-nav"', false)
            ->assertSee('class="pagination"', false)
            ->assertSee('1-25')
            ->assertSee('/')
            ->assertSee('Page 1/2')
            ->assertSee('Paged Activity #001')
            ->assertDontSee('Paged Activity #030');

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.activities.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Paged Activity #030')
            ->assertDontSee('Paged Activity #001');

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.activities.index', ['page' => 2, 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Paged Activity #011')
            ->assertDontSee('Paged Activity #001')
            ->assertDontSee('Paged Activity #021');
    }
}
