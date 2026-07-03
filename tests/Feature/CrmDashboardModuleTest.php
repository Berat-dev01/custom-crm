<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmDemoSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Activity;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use App\Crm\Models\Task as CrmTask;
use App\Crm\Services\Dashboard\DashboardReport;
use Tests\TestCase;

class CrmDashboardModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $sales;

    private DealStage $openStage;

    protected function setUp(): void
    {
        parent::setUp();

        // Pin the clock to mid-month so relative dates (e.g. subDays(5))
        // never cross a month boundary and break trend assertions.
        $this->travelTo(now()->startOfMonth()->addDays(14)->setTime(10, 0));

        $this->seed(CrmPermissionSeeder::class);
        $this->seed(CrmDealStageSeeder::class);

        $this->manager = User::factory()->create(['name' => 'Manager User'])->assignRole('crm_manager');
        $this->sales = User::factory()->create(['name' => 'Sales User'])->assignRole('crm_sales');
        $this->openStage = DealStage::query()->where('is_won', false)->where('is_lost', false)->ordered()->firstOrFail();

        $this->seedDashboardRecords();
    }

    public function test_manager_dashboard_sees_full_team_metrics(): void
    {
        $report = app(DashboardReport::class)->build(
            Request::create('/admin/crm', 'GET', ['period' => 'this_month']),
            $this->manager
        );

        $this->assertTrue($report['canViewAll']);
        $this->assertSame(2, $report['stats']['contacts']);
        $this->assertSame(2, $report['stats']['companies']);
        $this->assertSame(2, $report['stats']['open_deals']);
        $this->assertSame(3000.0, $report['stats']['open_pipeline_value']);
        $this->assertSame(1000.0, $report['stats']['weighted_pipeline_value']);
        $this->assertSame(7000.0, $report['stats']['won_deal_value']);
        $this->assertSame(2, $report['stats']['overdue_tasks']);
        $this->assertSame(1, $report['stats']['sent_quotes']);
        $this->assertSame(1, $report['stats']['accepted_quotes']);
    }

    public function test_sales_dashboard_is_scoped_to_own_records(): void
    {
        $report = app(DashboardReport::class)->build(
            Request::create('/admin/crm', 'GET', ['period' => 'this_month']),
            $this->sales
        );

        $this->assertFalse($report['canViewAll']);
        $this->assertSame(1, $report['stats']['contacts']);
        $this->assertSame(1, $report['stats']['companies']);
        $this->assertSame(1, $report['stats']['open_deals']);
        $this->assertSame(2000.0, $report['stats']['open_pipeline_value']);
        $this->assertSame(500.0, $report['stats']['weighted_pipeline_value']);
        $this->assertSame(4000.0, $report['stats']['won_deal_value']);
        $this->assertSame(1, $report['stats']['overdue_tasks']);
        $this->assertSame('Sales Open Deal', $report['topOpenDeals']->first()->title);
    }

    public function test_custom_date_range_limits_time_based_dashboard_metrics(): void
    {
        Deal::factory()->won()->create([
            'stage_id' => $this->openStage->id,
            'owner_id' => $this->sales->id,
            'value' => 9000,
            'closed_at' => now()->subMonths(3),
            'title' => 'Old Won Deal',
        ]);

        $report = app(DashboardReport::class)->build(
            Request::create('/admin/crm', 'GET', [
                'period' => 'custom',
                'date_from' => now()->startOfMonth()->format('Y-m-d'),
                'date_to' => now()->endOfMonth()->format('Y-m-d'),
            ]),
            $this->sales
        );

        $this->assertSame(4000.0, $report['stats']['won_deal_value']);
        $this->assertSame(now()->startOfMonth()->format('Y-m-d').' - '.now()->endOfMonth()->format('Y-m-d'), $report['range']['label']);
    }

    public function test_period_trend_changes_with_selected_period(): void
    {
        Deal::factory()->won()->create([
            'stage_id' => $this->openStage->id,
            'owner_id' => $this->sales->id,
            'value' => 2500,
            'closed_at' => now()->subDays(5),
            'title' => 'Older Won Deal',
        ]);

        $todayReport = app(DashboardReport::class)->build(
            Request::create('/admin/crm', 'GET', ['period' => 'today']),
            $this->manager
        );

        $monthReport = app(DashboardReport::class)->build(
            Request::create('/admin/crm', 'GET', ['period' => 'this_month']),
            $this->manager
        );

        $this->assertSame(2, collect($todayReport['monthlyTrend'])->sum('won_count'));
        $this->assertSame(3, collect($monthReport['monthlyTrend'])->sum('won_count'));
        $this->assertGreaterThan(count($todayReport['monthlyTrend']), count($monthReport['monthlyTrend']));
    }

    public function test_dashboard_region_supports_ajax_refresh(): void
    {
        $this->actingAs($this->manager, 'admin')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('crm.dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('crm-dashboard-region', false)
            ->assertSee(__('Period Won/Lost Trend'));
    }

    public function test_dashboard_page_renders_demo_seed_with_meaningful_sections(): void
    {
        $this->seed(CrmDemoSeeder::class);
        $user = User::query()->where('email', 'crm.manager@example.com')->firstOrFail();

        $this->actingAs($user, 'admin')
            ->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSee(__('Sales Dashboard'))
            ->assertSee(__('Open Pipeline'))
            ->assertSee(__('Pipeline by Stage'))
            ->assertSee(__('Period Won/Lost Trend'))
            ->assertSee(__('Upcoming Tasks'))
            ->assertSee(__('Recent Activities'))
            ->assertSee(__('Highest Value Open Deals'))
            ->assertSee(__('Quote Status Distribution'))
            ->assertSee('data-crm-dashboard-expand', false)
            ->assertSee('data-crm-paginate', false)
            ->assertSee('crm-dashboard-panel', false);
    }

    public function test_sales_dashboard_page_does_not_render_other_sales_top_deals(): void
    {
        $this->actingAs($this->sales, 'admin')
            ->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSee('Sales Open Deal')
            ->assertDontSee('Manager Open Deal');
    }

    private function seedDashboardRecords(): void
    {
        $managerCompany = Company::factory()->create(['owner_id' => $this->manager->id, 'name' => 'Manager Company']);
        $salesCompany = Company::factory()->create(['owner_id' => $this->sales->id, 'name' => 'Sales Company']);
        $managerContact = Contact::factory()->create(['owner_id' => $this->manager->id, 'company_id' => $managerCompany->id, 'full_name' => 'Manager Contact']);
        $salesContact = Contact::factory()->create(['owner_id' => $this->sales->id, 'company_id' => $salesCompany->id, 'full_name' => 'Sales Contact']);

        $managerOpenDeal = Deal::factory()->create([
            'stage_id' => $this->openStage->id,
            'owner_id' => $this->manager->id,
            'contact_id' => $managerContact->id,
            'company_id' => $managerCompany->id,
            'title' => 'Manager Open Deal',
            'value' => 1000,
            'probability' => 50,
            'status' => 'open',
        ]);
        $salesOpenDeal = Deal::factory()->create([
            'stage_id' => $this->openStage->id,
            'owner_id' => $this->sales->id,
            'contact_id' => $salesContact->id,
            'company_id' => $salesCompany->id,
            'title' => 'Sales Open Deal',
            'value' => 2000,
            'probability' => 25,
            'status' => 'open',
        ]);
        Deal::factory()->won()->create([
            'stage_id' => $this->openStage->id,
            'owner_id' => $this->manager->id,
            'contact_id' => $managerContact->id,
            'company_id' => $managerCompany->id,
            'title' => 'Manager Won Deal',
            'value' => 3000,
            'closed_at' => now(),
        ]);
        Deal::factory()->won()->create([
            'stage_id' => $this->openStage->id,
            'owner_id' => $this->sales->id,
            'contact_id' => $salesContact->id,
            'company_id' => $salesCompany->id,
            'title' => 'Sales Won Deal',
            'value' => 4000,
            'closed_at' => now(),
        ]);

        CrmTask::factory()->create(['assigned_to' => $this->manager->id, 'title' => 'Manager overdue', 'due_at' => now()->subDay()]);
        CrmTask::factory()->create(['assigned_to' => $this->sales->id, 'title' => 'Sales overdue', 'due_at' => now()->subDay()]);
        CrmTask::factory()->create(['assigned_to' => $this->sales->id, 'title' => 'Sales upcoming', 'due_at' => now()->addDay()]);

        Quote::factory()->create([
            'quote_number' => 'DASH-MANAGER-001',
            'owner_id' => $this->manager->id,
            'contact_id' => $managerContact->id,
            'company_id' => $managerCompany->id,
            'deal_id' => $managerOpenDeal->id,
            'status' => 'sent',
            'created_at' => now(),
        ]);
        Quote::factory()->accepted()->create([
            'quote_number' => 'DASH-SALES-001',
            'owner_id' => $this->sales->id,
            'contact_id' => $salesContact->id,
            'company_id' => $salesCompany->id,
            'deal_id' => $salesOpenDeal->id,
            'status' => 'accepted',
            'created_at' => now(),
        ]);

        Activity::factory()->create(['user_id' => $this->manager->id, 'subject' => 'Manager activity', 'occurred_at' => now()]);
        Activity::factory()->create(['user_id' => $this->sales->id, 'subject' => 'Sales activity', 'occurred_at' => now()]);
    }
}
