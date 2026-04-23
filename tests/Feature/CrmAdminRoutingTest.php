<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Tests\TestCase;

class CrmAdminRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_admin_module_routes_are_registered(): void
    {
        foreach ($this->routeNames() as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing route [{$routeName}].");
        }
    }

    public function test_crm_admin_routes_require_authenticated_user(): void
    {
        $this->get('/admin/crm/contacts')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_login_allows_demo_user_to_open_crm_dashboard(): void
    {
        $this->seed(CrmPermissionSeeder::class);

        User::factory()->create([
            'email' => 'crm.owner@example.com',
            'password' => 'password',
        ])->assignRole('crm_owner');

        $this->post('/admin/login', [
            'email' => 'crm.owner@example.com',
            'password' => 'password',
        ])->assertRedirect(route('crm.dashboard'));

        $this->get('/admin/crm')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_authorized_user_can_open_module_indexes(): void
    {
        $this->seed(CrmPermissionSeeder::class);

        $user = User::factory()->create()->assignRole('crm_owner');

        foreach ($this->modulePaths() as $path => $label) {
            $this->actingAs($user, 'admin')
                ->get($path)
                ->assertOk()
                ->assertSee($label);
        }
    }

    public function test_ai_admin_routes_are_registered_and_authorized(): void
    {
        $this->seed(CrmPermissionSeeder::class);

        $user = User::factory()->create()->assignRole('crm_owner');

        $this->actingAs($user, 'admin')
            ->postJson('/admin/crm/ai/summarize-note')
            ->assertAccepted();

        $this->actingAs($user, 'admin')
            ->postJson('/admin/crm/ai/draft-email')
            ->assertAccepted();
    }

    public function test_public_frontend_does_not_load_crm_admin_shell(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('crm-admin-shell')
            ->assertDontSee('vendor/crm/css/crm.css');
    }

    public function test_admin_panel_layout_namespace_is_available(): void
    {
        $this->assertTrue(view()->exists('admin-panel::layouts.app'));
    }

    /**
     * @return list<string>
     */
    private function routeNames(): array
    {
        return [
            'crm.dashboard',
            'crm.search',
            'crm.contacts.index',
            'crm.contacts.create',
            'crm.contacts.store',
            'crm.contacts.show',
            'crm.contacts.edit',
            'crm.contacts.update',
            'crm.contacts.destroy',
            'crm.contacts.export',
            'crm.contacts.import',
            'crm.contacts.template',
            'crm.contacts.import.preview',
            'crm.contacts.import.store',
            'crm.companies.index',
            'crm.companies.export',
            'crm.companies.import',
            'crm.companies.template',
            'crm.companies.import.preview',
            'crm.companies.import.store',
            'crm.deal-stages.index',
            'crm.deal-stages.create',
            'crm.deal-stages.store',
            'crm.deal-stages.edit',
            'crm.deal-stages.update',
            'crm.deal-stages.destroy',
            'crm.deal-stages.reorder',
            'crm.deals.index',
            'crm.deals.create',
            'crm.deals.store',
            'crm.deals.show',
            'crm.deals.edit',
            'crm.deals.update',
            'crm.deals.destroy',
            'crm.deals.tasks.store',
            'crm.deals.quotes.store',
            'crm.deals.activities.store',
            'crm.deals.stage',
            'crm.deals.close-won',
            'crm.deals.close-lost',
            'crm.deals.move',
            'crm.deals.export',
            'crm.deals.import',
            'crm.deals.template',
            'crm.deals.import.preview',
            'crm.deals.import.store',
            'crm.tasks.index',
            'crm.tasks.my',
            'crm.tasks.today',
            'crm.tasks.overdue',
            'crm.tasks.create',
            'crm.tasks.store',
            'crm.tasks.show',
            'crm.tasks.edit',
            'crm.tasks.update',
            'crm.tasks.destroy',
            'crm.tasks.complete',
            'crm.quotes.index',
            'crm.quotes.create',
            'crm.quotes.store',
            'crm.quotes.show',
            'crm.quotes.edit',
            'crm.quotes.update',
            'crm.quotes.destroy',
            'crm.quotes.send',
            'crm.quotes.accept',
            'crm.quotes.reject',
            'crm.quotes.expire',
            'crm.quotes.duplicate',
            'crm.quotes.preview',
            'crm.quotes.download',
            'crm.quotes.export',
            'crm.activities.index',
            'crm.activities.create',
            'crm.activities.store',
            'crm.activities.show',
            'crm.activities.edit',
            'crm.activities.update',
            'crm.activities.destroy',
            'crm.tags.index',
            'crm.tags.create',
            'crm.tags.store',
            'crm.tags.show',
            'crm.tags.edit',
            'crm.tags.update',
            'crm.tags.destroy',
            'crm.tags.bulk',
            'crm.saved-filters.store',
            'crm.saved-filters.apply',
            'crm.saved-filters.destroy',
            'crm.settings.index',
            'crm.settings.update',
            'crm.imports.errors',
            'crm.ai.summarize',
            'crm.ai.summarize-note',
            'crm.ai.draft-email',
            'crm.ai.follow-up',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function modulePaths(): array
    {
        return [
            '/admin/crm/contacts' => 'Contacts',
            '/admin/crm/companies' => 'Companies',
            '/admin/crm/deal-stages' => 'Deal Stages',
            '/admin/crm/deals' => 'Deals',
            '/admin/crm/tasks' => 'Tasks',
            '/admin/crm/quotes' => 'Quotes',
            '/admin/crm/activities' => 'Activities',
            '/admin/crm/tags' => 'Tags',
        ];
    }
}
