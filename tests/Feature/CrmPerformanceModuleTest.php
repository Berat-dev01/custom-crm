<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Services\Dashboard\DashboardReport;
use App\Crm\Services\Deals\DealQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmPerformanceModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->seed(CrmDealStageSeeder::class);
        $this->owner = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_kanban_pipeline_limits_deals_per_stage_but_keeps_aggregates(): void
    {
        config([
            'crm.performance.kanban_per_stage_limit' => 5,
            'crm.performance.kanban_per_stage_max_limit' => 10,
        ]);

        $stage = DealStage::query()->where('slug', 'new')->firstOrFail();
        Deal::factory()
            ->count(12)
            ->sequence(fn ($sequence): array => [
                'stage_id' => $stage->id,
                'owner_id' => $this->owner->id,
                'status' => 'open',
                'position' => $sequence->index + 1,
                'value' => 100,
            ])
            ->create();

        $pipeline = app(DealQuery::class)->pipeline(Request::create('/admin/crm/deals', 'GET'));
        $newStage = $pipeline->firstWhere('id', $stage->id);

        $this->assertNotNull($newStage);
        $this->assertCount(5, $newStage->deals);
        $this->assertSame(12, $newStage->deals_count);
        $this->assertSame(1200.0, $newStage->pipeline_value);
        $this->assertTrue((bool) $newStage->has_more_deals);
        $this->assertSame([1, 2, 3, 4, 5], $newStage->deals->pluck('position')->all());
    }

    public function test_dashboard_report_keeps_query_count_bounded(): void
    {
        $stage = DealStage::query()->where('slug', 'new')->firstOrFail();

        Deal::factory()->count(30)->create([
            'stage_id' => $stage->id,
            'owner_id' => $this->owner->id,
            'status' => 'open',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $report = app(DashboardReport::class)->build(
            Request::create('/admin/crm', 'GET', ['period' => 'this_month']),
            $this->owner
        );

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(24, $queryCount);
        $this->assertLessThanOrEqual(6, $report['topOpenDeals']->count());
        $this->assertArrayHasKey('pipelineByStage', $report);
    }
}
