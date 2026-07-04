<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmSetupScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_checklist_reflects_configuration_state(): void
    {
        $this->seed(CrmPermissionSeeder::class);
        $this->seed(CrmDealStageSeeder::class);
        $owner = User::factory()->create()->assignRole('crm_owner');

        $this->actingAs($owner, 'admin')
            ->get(route('crm.setup.index'))
            ->assertOk()
            ->assertSee(__('Company profile'))
            ->assertSee(__('Sales pipeline stages'))
            ->assertSee(__('Invite your team'));
    }

    public function test_setup_requires_settings_permission(): void
    {
        $this->seed(CrmPermissionSeeder::class);
        $sales = User::factory()->create()->assignRole('crm_sales');

        $this->actingAs($sales, 'admin')
            ->get(route('crm.setup.index'))
            ->assertForbidden();
    }
}
