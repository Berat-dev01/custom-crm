<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\SavedFilter;
use Tests\TestCase;

class CrmSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->seed(CrmDealStageSeeder::class);

        $this->viewer = User::factory()->create()->assignRole('crm_viewer');
        $this->owner  = User::factory()->create()->assignRole('crm_owner');
    }

    // --- Deal sub-resource creation requires create permission (#1) ---

    public function test_viewer_cannot_store_task_on_deal(): void
    {
        $deal = Deal::factory()->for(DealStage::first(), 'stage')->create();

        $this->actingAs($this->viewer)
            ->post(route('crm.deals.tasks.store', $deal), [
                'title'    => 'Test task',
                'priority' => 'normal',
            ])
            ->assertForbidden();
    }

    public function test_viewer_cannot_store_activity_on_deal(): void
    {
        $deal = Deal::factory()->for(DealStage::first(), 'stage')->create();

        $this->actingAs($this->viewer)
            ->post(route('crm.deals.activities.store', $deal), [
                'type'    => 'note',
                'subject' => 'Test note',
            ])
            ->assertForbidden();
    }

    public function test_viewer_cannot_store_quote_on_deal(): void
    {
        $deal = Deal::factory()->for(DealStage::first(), 'stage')->create();

        $this->actingAs($this->viewer)
            ->post(route('crm.deals.quotes.store', $deal), [
                'item_name'  => 'Service',
                'quantity'   => 1,
                'unit_price' => 100,
                'currency'   => 'TRY',
            ])
            ->assertForbidden();
    }

    // --- Import requires module-level permission (#2) ---

    public function test_viewer_cannot_import_contacts(): void
    {
        $this->actingAs($this->viewer)
            ->post(route('crm.contacts.import.store'), [])
            ->assertForbidden();
    }

    public function test_viewer_cannot_import_deals(): void
    {
        $this->actingAs($this->viewer)
            ->post(route('crm.deals.import.store'), [])
            ->assertForbidden();
    }

    // --- Bulk delete respects max:500 limit (#6) ---

    public function test_bulk_delete_contacts_rejects_more_than_500_ids(): void
    {
        $ids = range(1, 501);

        $this->actingAs($this->owner)
            ->delete(route('crm.contacts.bulk-delete'), ['contact_ids' => $ids])
            ->assertStatus(302)
            ->assertSessionHasErrors('contact_ids');
    }

    public function test_bulk_delete_deals_rejects_more_than_500_ids(): void
    {
        $ids = range(1, 501);

        $this->actingAs($this->owner)
            ->delete(route('crm.deals.bulk-delete'), ['record_ids' => $ids])
            ->assertStatus(302)
            ->assertSessionHasErrors('record_ids');
    }

    // --- SavedFilter: non-owner cannot delete another user's filter (#11) ---

    public function test_viewer_cannot_delete_another_users_saved_filter(): void
    {
        $otherUser = User::factory()->create()->assignRole('crm_sales');

        $filter = SavedFilter::create([
            'name'       => 'Other user filter',
            'module'     => 'contacts',
            'filters'    => [],
            'visibility' => 'shared',
            'user_id'    => $otherUser->id,
        ]);

        $this->actingAs($this->viewer)
            ->delete(route('crm.saved-filters.destroy', $filter))
            ->assertForbidden();
    }

    public function test_owner_of_filter_can_delete_own_filter(): void
    {
        $filter = SavedFilter::create([
            'name'       => 'My filter',
            'module'     => 'contacts',
            'filters'    => [],
            'visibility' => 'shared',
            'user_id'    => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->delete(route('crm.saved-filters.destroy', $filter))
            ->assertRedirect();

        $this->assertSoftDeleted('crm_saved_filters', ['id' => $filter->id]);
    }

    // --- API: bearer token required, session alone is not enough (#3) ---

    public function test_api_rejects_session_only_request_without_bearer_token(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/crm/contacts')
            ->assertUnauthorized();
    }

    // --- Global security headers ---

    public function test_responses_include_security_headers(): void
    {
        $response = $this->get('/admin/login');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
