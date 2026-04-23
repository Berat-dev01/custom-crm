<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sanalkopru\Crm\Actions\Deals\MoveDealToStage;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Tests\TestCase;

class DealStageTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_open_won_and_lost_stage_business_rules(): void
    {
        $user = User::factory()->create();
        $openStage = DealStage::factory()->create([
            'name' => 'Unit Open',
            'slug' => 'unit-open',
            'probability' => 40,
            'is_won' => false,
            'is_lost' => false,
        ]);
        $wonStage = DealStage::factory()->won()->create(['slug' => 'unit-won']);
        $lostStage = DealStage::factory()->lost()->create(['slug' => 'unit-lost']);
        $deal = Deal::factory()->create([
            'stage_id' => $openStage->id,
            'status' => 'open',
            'probability' => 40,
            'closed_at' => null,
            'lost_reason' => null,
        ]);

        $mover = app(MoveDealToStage::class);

        $wonDeal = $mover->handle($deal, $wonStage, 1, null, $user);
        $this->assertSame('won', $wonDeal->status);
        $this->assertSame(100, $wonDeal->probability);
        $this->assertNotNull($wonDeal->closed_at);
        $this->assertNull($wonDeal->lost_reason);

        $lostDeal = $mover->handle($wonDeal, $lostStage, 1, 'Budget cancelled', $user);
        $this->assertSame('lost', $lostDeal->status);
        $this->assertSame(0, $lostDeal->probability);
        $this->assertSame('Budget cancelled', $lostDeal->lost_reason);
        $this->assertNotNull($lostDeal->closed_at);

        $reopenedDeal = $mover->handle($lostDeal, $openStage, 1, null, $user);
        $this->assertSame('open', $reopenedDeal->status);
        $this->assertSame(40, $reopenedDeal->probability);
        $this->assertNull($reopenedDeal->closed_at);
        $this->assertNull($reopenedDeal->lost_reason);
    }
}
