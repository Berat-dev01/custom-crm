<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Activity;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use App\Crm\Models\Tag;
use App\Crm\Models\Task as CrmTask;
use Tests\TestCase;

class CrmDealsPipelineModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_kanban_index_groups_deals_by_ordered_stages_and_filters(): void
    {
        $stageA = DealStage::factory()->create(['name' => 'New', 'slug' => 'new', 'position' => 1, 'is_won' => false, 'is_lost' => false]);
        $stageB = DealStage::factory()->create(['name' => 'Proposal', 'slug' => 'proposal', 'position' => 2, 'is_won' => false, 'is_lost' => false]);
        $owner = User::factory()->create(['name' => 'Pipeline Owner']);
        $tag = Tag::factory()->create(['name' => 'Enterprise']);
        $matching = Deal::factory()->create([
            'title' => 'Enterprise Upgrade',
            'stage_id' => $stageA->id,
            'owner_id' => $owner->id,
            'value' => 50000,
            'expected_close_date' => '2026-05-10',
            'status' => 'open',
            'position' => 1,
        ]);
        $matching->tags()->attach($tag);
        Deal::factory()->create([
            'title' => 'Small Renewal',
            'stage_id' => $stageB->id,
            'owner_id' => User::factory(),
            'value' => 500,
            'expected_close_date' => '2026-06-01',
            'status' => 'open',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.deals.index', [
                'owner_id' => $owner->id,
                'tag_id' => $tag->id,
                'expected_from' => '2026-05-01',
                'expected_to' => '2026-05-31',
                'value_min' => 1000,
                'value_max' => 100000,
                'status' => 'open',
            ]))
            ->assertOk()
            ->assertSee(__('Deals Pipeline'))
            ->assertSee(__('New Deal'))
            ->assertSee('Proposal')
            ->assertSee('Enterprise Upgrade')
            ->assertDontSee('Small Renewal');
    }

    public function test_list_view_and_show_include_sales_context(): void
    {
        $stage = DealStage::factory()->create(['name' => 'Negotiation', 'slug' => 'negotiation', 'position' => 1, 'is_won' => false, 'is_lost' => false]);
        $company = Company::factory()->create(['name' => 'Acme Group']);
        $contact = Contact::factory()->create(['full_name' => 'Ada Sales']);
        $deal = Deal::factory()->create([
            'title' => 'Annual License',
            'stage_id' => $stage->id,
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'owner_id' => $this->admin->id,
            'value' => 120000,
            'probability' => 50,
        ]);
        CrmTask::factory()->create([
            'taskable_type' => $deal->getMorphClass(),
            'taskable_id' => $deal->id,
            'title' => 'Send proposal',
            'completed_at' => null,
        ]);
        Quote::factory()->create(['deal_id' => $deal->id, 'quote_number' => 'CRM-DEAL-1']);
        Activity::factory()->create([
            'activityable_type' => $deal->getMorphClass(),
            'activityable_id' => $deal->id,
            'subject' => 'Discovery call',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.deals.index', ['view' => 'list']))
            ->assertOk()
            ->assertSee('Annual License')
            ->assertSee('Acme Group');

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.deals.show', $deal))
            ->assertOk()
            ->assertSee(__('Weighted Value'))
            ->assertSee('Ada Sales')
            ->assertSee('Send proposal')
            ->assertSee('CRM-DEAL-1')
            ->assertSee('Discovery call');
    }

    public function test_deal_can_be_created_updated_and_deleted(): void
    {
        $stage = DealStage::factory()->create(['name' => 'New', 'slug' => 'new', 'position' => 1, 'is_won' => false, 'is_lost' => false]);
        $company = Company::factory()->create();
        $contact = Contact::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('crm.deals.store'), [
                'title' => 'New Implementation',
                'company_id' => $company->id,
                'contact_id' => $contact->id,
                'stage_id' => $stage->id,
                'value' => 75000,
                'currency' => 'TRY',
                'probability' => 35,
                'expected_close_date' => '2026-05-20',
                'status' => 'open',
                'owner_id' => $this->admin->id,
                'tag_ids' => [$tag->id],
                'custom_fields_json' => '{"source":"partner"}',
            ]);

        $deal = Deal::query()->where('title', 'New Implementation')->firstOrFail();

        $response->assertRedirect(route('crm.deals.show', $deal));
        $this->assertTrue($deal->tags->contains($tag));
        $this->assertSame(['source' => 'partner'], $deal->custom_fields);

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.deals.update', $deal), [
                'title' => 'Expanded Implementation',
                'company_id' => $company->id,
                'contact_id' => $contact->id,
                'stage_id' => $stage->id,
                'value' => 90000,
                'currency' => 'TRY',
                'probability' => 45,
                'expected_close_date' => '2026-06-01',
                'status' => 'open',
                'owner_id' => $this->admin->id,
                'tag_ids' => [],
                'custom_fields_json' => '{"source":"direct"}',
            ])
            ->assertRedirect(route('crm.deals.show', $deal));

        $deal->refresh();
        $this->assertSame('Expanded Implementation', $deal->title);
        $this->assertSame('90000.00', $deal->value);
        $this->assertSame(['source' => 'direct'], $deal->custom_fields);
        $this->assertCount(0, $deal->tags);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.deals.destroy', $deal))
            ->assertRedirect(route('crm.deals.index'));

        $this->assertSoftDeleted('deals', ['id' => $deal->id]);
    }

    public function test_move_preserves_stage_and_position_after_refresh(): void
    {
        $source = DealStage::factory()->create(['name' => 'Source', 'slug' => 'source', 'position' => 1, 'probability' => 20, 'is_won' => false, 'is_lost' => false]);
        $target = DealStage::factory()->create(['name' => 'Target', 'slug' => 'target', 'position' => 2, 'probability' => 70, 'is_won' => false, 'is_lost' => false]);
        $existingFirst = Deal::factory()->create(['stage_id' => $target->id, 'position' => 1]);
        $existingSecond = Deal::factory()->create(['stage_id' => $target->id, 'position' => 2]);
        $moving = Deal::factory()->create(['stage_id' => $source->id, 'position' => 1, 'probability' => 20]);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('crm.deals.move', $moving), [
                'stage_id' => $target->id,
                'position' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('deal.stage_id', $target->id)
            ->assertJsonPath('deal.position', 2)
            ->assertJsonPath('deal.probability', 70);

        $this->assertSame([$existingFirst->id, $moving->id, $existingSecond->id], Deal::query()
            ->where('stage_id', $target->id)
            ->orderBy('position')
            ->pluck('id')
            ->all());
    }

    public function test_move_within_same_stage_normalizes_order_without_duplicate_positions(): void
    {
        $stage = DealStage::factory()->create(['name' => 'Open', 'slug' => 'open', 'position' => 1, 'is_won' => false, 'is_lost' => false]);
        $first = Deal::factory()->create(['stage_id' => $stage->id, 'position' => 1]);
        $second = Deal::factory()->create(['stage_id' => $stage->id, 'position' => 2]);
        $third = Deal::factory()->create(['stage_id' => $stage->id, 'position' => 3]);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('crm.deals.move', $third), [
                'stage_id' => $stage->id,
                'position' => 1,
            ])
            ->assertOk();

        $this->assertSame([$third->id, $first->id, $second->id], Deal::query()
            ->where('stage_id', $stage->id)
            ->orderBy('position')
            ->pluck('id')
            ->all());
        $this->assertSame([1, 2, 3], Deal::query()
            ->where('stage_id', $stage->id)
            ->orderBy('position')
            ->pluck('position')
            ->all());
    }
}
