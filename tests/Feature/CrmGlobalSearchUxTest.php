<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;
use Tests\TestCase;

class CrmGlobalSearchUxTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_global_search_finds_contacts_companies_deals_and_quotes(): void
    {
        $company = Company::factory()->create(['name' => 'Northwind Logistics']);
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'full_name' => 'Nora Northwind',
            'email' => 'nora@northwind.test',
        ]);
        $stage = DealStage::factory()->create(['name' => 'Demo', 'slug' => 'demo', 'position' => 1]);
        $deal = Deal::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'stage_id' => $stage->id,
            'title' => 'Northwind Expansion',
            'value' => 12500,
            'currency' => 'TRY',
        ]);
        $quote = Quote::factory()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'deal_id' => $deal->id,
            'quote_number' => 'NW-QUOTE-100',
            'grand_total' => 12500,
            'currency' => 'TRY',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.search', ['q' => 'Northwind']))
            ->assertOk()
            ->assertSee('Nora Northwind')
            ->assertSee('Northwind Logistics')
            ->assertSee('Northwind Expansion')
            ->assertSee($quote->quote_number)
            ->assertSee('12.500,00 TRY');
    }

    public function test_global_search_respects_module_permissions(): void
    {
        Contact::factory()->create(['full_name' => 'Permission Contact', 'email' => 'permission@example.test']);
        Company::factory()->create(['name' => 'Permission Company']);
        $user = User::factory()->create();
        $user->givePermissionTo(['crm.dashboard.view', 'crm.contacts.view']);

        $this->actingAs($user, 'admin')
            ->get(route('crm.search', ['q' => 'Permission']))
            ->assertOk()
            ->assertSee('Permission Contact')
            ->assertDontSee('Permission Company');
    }

    public function test_crm_indexes_show_global_search_and_empty_state_action(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.contacts.index'))
            ->assertOk()
            ->assertSee('data-admin-command-palette', false)
            ->assertSee(__('Overview'))
            ->assertSee(__('Sales'))
            ->assertSee(__('Customers'))
            ->assertSee(__('Operations'))
            ->assertSee(__('No contacts found.'))
            ->assertSee(__('New Contact'));
    }
}
