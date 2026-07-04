<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Contact;
use App\Crm\Services\Audit\CrmAuditLogger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmAuditLogScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->owner = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_only_settings_managers_can_open_audit_log(): void
    {
        $sales = User::factory()->create()->assignRole('crm_sales');

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.audit-logs.index'))
            ->assertOk()
            ->assertSee(__('Audit Log'));

        $this->actingAs($sales, 'admin')
            ->get(route('crm.audit-logs.index'))
            ->assertForbidden();
    }

    public function test_audit_entries_are_listed_and_filterable(): void
    {
        $contact = Contact::factory()->create();
        $otherUser = User::factory()->create(['name' => 'Other Actor']);

        app(CrmAuditLogger::class)->record('crm.contact.updated', $contact, $this->owner, ['email' => 'a@a.test'], ['email' => 'b@b.test']);
        app(CrmAuditLogger::class)->record('crm.deal.won', null, $otherUser, [], []);

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.audit-logs.index'))
            ->assertOk()
            ->assertSee('crm.contact.updated')
            ->assertSee('crm.deal.won');

        // Filter by event
        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.audit-logs.index', ['event' => 'contact.updated']))
            ->assertOk()
            ->assertSee('crm.contact.updated')
            ->assertDontSee('crm.deal.won');

        // Filter by user
        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.audit-logs.index', ['user_id' => $otherUser->id]))
            ->assertOk()
            ->assertSee('crm.deal.won')
            ->assertDontSee('crm.contact.updated');
    }
}
