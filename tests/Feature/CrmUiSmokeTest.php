<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmUiSmokeTest extends TestCase
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

    public function test_kanban_view_exposes_drag_drop_hooks(): void
    {
        $stage = DealStage::query()->where('slug', 'new')->firstOrFail();
        $deal = Deal::factory()->create([
            'title' => 'Smoke Kanban Deal',
            'stage_id' => $stage->id,
            'position' => 1,
        ]);

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.deals.index', ['view' => 'kanban']))
            ->assertOk()
            ->assertSee('data-crm-kanban-board', false)
            ->assertSee('data-crm-kanban-list', false)
            ->assertSee('data-stage-is-lost', false)
            ->assertSee('data-move-url="'.route('crm.deals.move', $deal).'"', false)
            ->assertSee('Smoke Kanban Deal');
    }

    public function test_quote_form_exposes_line_item_hooks(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.quotes.create'))
            ->assertOk()
            ->assertSee('data-crm-quote-form', false)
            ->assertSee('data-crm-add-quote-item', false)
            ->assertSee('data-crm-quote-items', false)
            ->assertSee('data-admin-select', false)
            ->assertSee('data-admin-select-native', false)
            ->assertSee('data-default-tax-rate', false)
            ->assertSee('name="items[0][name]"', false)
            ->assertSee('name="items[0][quantity]"', false)
            ->assertSee('name="items[0][unit_price]"', false);
    }

    public function test_list_views_expose_filter_ajax_and_bulk_hooks(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.contacts.index'))
            ->assertOk()
            ->assertSee('id="crm-contacts-list"', false)
            ->assertSee('data-admin-ajax-list', false)
            ->assertSee('data-admin-ajax-filter-form', false)
            ->assertSee('data-admin-filter-toggle', false)
            ->assertSee('data-admin-bulk-actions', false)
            ->assertSee('data-admin-bulk-toggle-all', false);

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.tasks.today'))
            ->assertOk()
            ->assertSee('data-admin-ajax-target="crm-tasks-list"', false);

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.deals.index', ['view' => 'kanban']))
            ->assertOk()
            ->assertSee('data-admin-ajax-target="crm-deals-list"', false);
    }
}
