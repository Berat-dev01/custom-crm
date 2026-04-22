<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Services\Authorization\CrmAuthorization;
use Tests\TestCase;

class CrmAuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_delete_records(): void
    {
        $this->seed(CrmPermissionSeeder::class);

        $viewer = User::factory()->create()->assignRole('crm_viewer');
        $contact = Contact::factory()->create();

        $this->assertFalse($viewer->can('delete', $contact));
    }

    public function test_sales_can_move_deals_but_cannot_manage_settings(): void
    {
        $this->seed(CrmPermissionSeeder::class);

        $sales = User::factory()->create()->assignRole('crm_sales');
        $deal = Deal::factory()->create();

        $this->assertTrue($sales->can('move', $deal));
        $this->assertFalse($sales->can('crm.settings.manage'));
    }

    public function test_manager_can_view_reports_and_full_pipeline(): void
    {
        $this->seed(CrmPermissionSeeder::class);

        $manager = User::factory()->create()->assignRole('crm_manager');

        $this->assertTrue($manager->can('crm.reports.view'));
        $this->assertTrue($manager->can('crm.pipeline.view'));
        $this->assertTrue($manager->can('viewAny', Deal::class));
    }

    public function test_permission_fallback_allows_authenticated_users_when_disabled(): void
    {
        config(['crm.permissions.enabled' => false]);

        $user = User::factory()->create();

        $this->assertTrue(app(CrmAuthorization::class)->can($user, 'crm.settings.manage'));
        $this->assertFalse(app(CrmAuthorization::class)->can(null, 'crm.settings.manage'));
    }

    public function test_dashboard_denies_users_without_permission(): void
    {
        $this->seed(CrmPermissionSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/crm')
            ->assertForbidden();
    }
}
