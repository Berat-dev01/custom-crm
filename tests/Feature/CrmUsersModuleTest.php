<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CrmUsersModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CrmPermissionSeeder::class);
    }

    private function owner(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('crm_owner');

        return $user;
    }

    private function viewer(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('crm_viewer');

        return $user;
    }

    public function test_owner_can_list_users(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner, 'admin')
            ->get(route('crm.users.index'))
            ->assertOk()
            ->assertSee($owner->name);
    }

    public function test_viewer_cannot_access_users(): void
    {
        $viewer = $this->viewer();

        $this->actingAs($viewer, 'admin')
            ->get(route('crm.users.index'))
            ->assertForbidden();
    }

    public function test_owner_can_create_user(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner, 'admin')
            ->post(route('crm.users.store'), [
                'name' => 'New Sales Rep',
                'email' => 'sales.rep@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'crm_role' => 'sales',
            ])
            ->assertRedirect(route('crm.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'sales.rep@example.com']);

        $created = User::where('email', 'sales.rep@example.com')->first();
        $this->assertTrue($created->hasRole('crm_sales', 'web'));
    }

    public function test_owner_can_update_user_role(): void
    {
        $owner = $this->owner();
        $user = $this->viewer();

        $this->actingAs($owner, 'admin')
            ->put(route('crm.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'crm_role' => 'sales',
            ])
            ->assertRedirect(route('crm.users.index'));

        $user->refresh();
        $this->assertTrue($user->hasRole('crm_sales', 'web'));
        $this->assertFalse($user->hasRole('crm_viewer', 'web'));
    }

    public function test_cannot_remove_last_owner_role(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner, 'admin')
            ->put(route('crm.users.update', $owner), [
                'name' => $owner->name,
                'email' => $owner->email,
                'crm_role' => 'sales',
            ])
            ->assertRedirect();

        $owner->refresh();
        $this->assertTrue($owner->hasRole('crm_owner', 'web'));
    }

    public function test_owner_can_toggle_active_status(): void
    {
        $owner = $this->owner();
        $user = $this->viewer();

        $this->assertTrue($user->is_active);

        $this->actingAs($owner, 'admin')
            ->patch(route('crm.users.toggle-active', $user))
            ->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->is_active);
    }

    public function test_cannot_deactivate_own_account(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner, 'admin')
            ->patch(route('crm.users.toggle-active', $owner))
            ->assertRedirect();

        $owner->refresh();
        $this->assertTrue($owner->is_active);
    }

    public function test_cannot_deactivate_last_owner(): void
    {
        $owner = $this->owner();

        $manager = User::factory()->create(['is_active' => true]);
        $manager->givePermissionTo(Permission::findByName('crm.users.manage', 'web'));

        $this->actingAs($manager, 'admin')
            ->patch(route('crm.users.toggle-active', $owner))
            ->assertRedirect();

        $owner->refresh();
        $this->assertTrue($owner->is_active);
    }

    public function test_owner_can_delete_user(): void
    {
        $owner = $this->owner();
        $user = $this->viewer();

        $this->actingAs($owner, 'admin')
            ->delete(route('crm.users.destroy', $user))
            ->assertRedirect(route('crm.users.index'));

        $this->assertNull(User::find($user->id));
    }

    public function test_cannot_delete_last_owner(): void
    {
        $owner = $this->owner();

        $manager = User::factory()->create(['is_active' => true]);
        $manager->givePermissionTo(Permission::findByName('crm.users.manage', 'web'));

        $this->actingAs($manager, 'admin')
            ->delete(route('crm.users.destroy', $owner))
            ->assertRedirect();

        $this->assertNotNull(User::find($owner->id));
    }

    public function test_inactive_user_is_blocked_from_crm(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('crm_viewer');

        $this->actingAs($user, 'admin')
            ->get(route('crm.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_users_redirect_points_to_crm_users(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner, 'admin')
            ->get('/admin/users')
            ->assertRedirect(route('crm.users.index'));
    }
}
