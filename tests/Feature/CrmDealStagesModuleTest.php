<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sanalkopru\Crm\Database\Seeders\CrmDealStageSeeder;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Tests\TestCase;

class CrmDealStagesModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_default_deal_stage_seeder_creates_ordered_pipeline(): void
    {
        $this->seed(CrmDealStageSeeder::class);

        $this->assertSame([
            'new',
            'contacted',
            'quote-preparing',
            'quote-sent',
            'negotiation',
            'won',
            'lost',
        ], DealStage::query()->ordered()->pluck('slug')->all());

        $this->assertTrue(DealStage::query()->where('slug', 'won')->firstOrFail()->is_won);
        $this->assertTrue(DealStage::query()->where('slug', 'lost')->firstOrFail()->is_lost);
    }

    public function test_only_settings_users_can_manage_deal_stages(): void
    {
        DealStage::factory()->create(['name' => 'New', 'slug' => 'new', 'is_won' => false, 'is_lost' => false]);
        $sales = User::factory()->create()->assignRole('crm_sales');

        $this->actingAs($sales, 'admin')
            ->get(route('crm.deal-stages.index'))
            ->assertForbidden();

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.deal-stages.index'))
            ->assertOk()
            ->assertSee(__('Deal Stages'))
            ->assertSee(__('New Stage'));
    }

    public function test_stage_can_be_created_updated_and_reordered(): void
    {
        $first = DealStage::factory()->create(['name' => 'Discovery', 'slug' => 'discovery', 'position' => 1, 'is_won' => false, 'is_lost' => false]);
        $second = DealStage::factory()->create(['name' => 'Proposal', 'slug' => 'proposal', 'position' => 2, 'is_won' => false, 'is_lost' => false]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.deal-stages.store'), [
                'name' => 'Contract Review',
                'slug' => '',
                'color' => '#0ea5e9',
                'position' => 3,
                'probability' => 80,
            ])
            ->assertRedirect(route('crm.deal-stages.index'));

        $created = DealStage::query()->where('slug', 'contract-review')->firstOrFail();

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.deal-stages.update', $created), [
                'name' => 'Contract Sent',
                'slug' => 'contract-sent',
                'color' => '#16a34a',
                'position' => 4,
                'probability' => 90,
                'is_won' => '1',
            ])
            ->assertRedirect(route('crm.deal-stages.index'));

        $created->refresh();
        $this->assertSame('Contract Sent', $created->name);
        $this->assertSame(100, $created->probability);
        $this->assertTrue($created->is_won);

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.deal-stages.reorder'), [
                'stages' => [
                    ['id' => $first->id, 'position' => 2],
                    ['id' => $second->id, 'position' => 1],
                    ['id' => $created->id, 'position' => 3],
                ],
            ])
            ->assertRedirect(route('crm.deal-stages.index'));

        $this->assertSame(['proposal', 'discovery', 'contract-sent'], DealStage::query()->ordered()->pluck('slug')->all());
    }

    public function test_stage_with_deals_must_be_moved_before_delete(): void
    {
        $source = DealStage::factory()->create(['name' => 'Source', 'slug' => 'source', 'position' => 1, 'is_won' => false, 'is_lost' => false]);
        $replacement = DealStage::factory()->create(['name' => 'Replacement', 'slug' => 'replacement', 'position' => 2, 'probability' => 30, 'is_won' => false, 'is_lost' => false]);
        $deal = Deal::factory()->create(['stage_id' => $source->id, 'status' => 'open']);

        $this->actingAs($this->admin, 'admin')
            ->from(route('crm.deal-stages.index'))
            ->delete(route('crm.deal-stages.destroy', $source))
            ->assertRedirect(route('crm.deal-stages.index'))
            ->assertSessionHasErrors('replacement_stage_id');

        $this->assertNotSoftDeleted('deal_stages', ['id' => $source->id]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.deal-stages.destroy', $source), [
                'replacement_stage_id' => $replacement->id,
            ])
            ->assertRedirect(route('crm.deal-stages.index'));

        $this->assertSoftDeleted('deal_stages', ['id' => $source->id]);
        $this->assertSame($replacement->id, $deal->refresh()->stage_id);
        $this->assertSame(30, $deal->probability);
    }

    public function test_deal_move_applies_won_lost_and_open_stage_behavior(): void
    {
        $open = DealStage::factory()->create(['name' => 'Open', 'slug' => 'open', 'position' => 1, 'probability' => 25, 'is_won' => false, 'is_lost' => false]);
        $won = DealStage::factory()->won()->create(['position' => 2]);
        $lost = DealStage::factory()->lost()->create(['position' => 3]);
        $deal = Deal::factory()->create(['stage_id' => $open->id, 'status' => 'open', 'probability' => 25]);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('crm.deals.move', $deal), [
                'stage_id' => $won->id,
                'position' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('deal.status', 'won')
            ->assertJsonPath('deal.probability', 100);

        $deal->refresh();
        $this->assertSame('won', $deal->status);
        $this->assertNotNull($deal->closed_at);
        $this->assertNull($deal->lost_reason);

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('crm.deals.move', $deal), [
                'stage_id' => $lost->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lost_reason');

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('crm.deals.move', $deal), [
                'stage_id' => $lost->id,
                'lost_reason' => 'Budget mismatch',
            ])
            ->assertOk()
            ->assertJsonPath('deal.status', 'lost')
            ->assertJsonPath('deal.probability', 0)
            ->assertJsonPath('deal.lost_reason', 'Budget mismatch');

        $this->actingAs($this->admin, 'admin')
            ->patchJson(route('crm.deals.move', $deal), [
                'stage_id' => $open->id,
            ])
            ->assertOk()
            ->assertJsonPath('deal.status', 'open')
            ->assertJsonPath('deal.probability', 25)
            ->assertJsonPath('deal.lost_reason', null);

        $deal->refresh();
        $this->assertNull($deal->closed_at);
        $this->assertNull($deal->lost_reason);
    }
}
