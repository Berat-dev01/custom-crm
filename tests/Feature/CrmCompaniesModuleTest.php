<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Activity;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Models\Tag;
use App\Crm\Models\Task as CrmTask;
use Tests\TestCase;

class CrmCompaniesModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_companies_index_filters_records(): void
    {
        Company::factory()->create(['name' => 'Acme Software', 'sector' => 'Technology']);
        Company::factory()->create(['name' => 'Retail Box', 'sector' => 'Retail']);

        $this->actingAs($this->admin, 'admin')
            ->get('/admin/crm/companies?search=Acme')
            ->assertOk()
            ->assertSee('Acme Software')
            ->assertDontSee('Retail Box');
    }

    public function test_company_can_be_created_updated_and_deleted(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->post('/admin/crm/companies', [
                'name' => 'Acme Holding',
                'email' => 'hello@acme.test',
                'phone' => '+905551112233',
                'website' => 'https://acme.test',
                'tax_number' => '1234567890',
                'tax_office' => 'Istanbul',
                'sector' => 'Technology',
                'address_line_1' => 'Main Street 1',
                'city' => 'Istanbul',
                'country' => 'TR',
                'owner_id' => $this->admin->id,
                'tag_ids' => [$tag->id],
                'custom_fields_json' => '{"erp_code":"ACME-001"}',
            ]);

        $company = Company::query()->where('name', 'Acme Holding')->firstOrFail();

        $response->assertRedirect(route('crm.companies.show', $company));
        $this->assertTrue($company->tags->contains($tag));

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.companies.update', $company), [
                'name' => 'Acme Group',
                'email' => 'hello@acme.test',
                'phone' => '+905551112233',
                'website' => 'https://acme.test',
                'tax_number' => '1234567890',
                'tax_office' => 'Istanbul',
                'sector' => 'Consulting',
                'address_line_1' => 'Main Street 2',
                'city' => 'Ankara',
                'country' => 'TR',
                'owner_id' => $this->admin->id,
                'tag_ids' => [],
                'custom_fields_json' => '{"erp_code":"ACME-002"}',
            ])
            ->assertRedirect(route('crm.companies.show', $company));

        $company->refresh();
        $this->assertSame('Acme Group', $company->name);
        $this->assertSame('Consulting', $company->sector);
        $this->assertSame(['erp_code' => 'ACME-002'], $company->custom_fields);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.companies.destroy', $company))
            ->assertRedirect(route('crm.companies.index'));

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_duplicate_company_name_and_tax_number_are_rejected(): void
    {
        Company::factory()->create([
            'name' => 'Duplicate Company',
            'tax_number' => '9988776655',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->from('/admin/crm/companies/create')
            ->post('/admin/crm/companies', [
                'name' => 'Duplicate Company',
                'tax_number' => '9988776655',
            ])
            ->assertRedirect('/admin/crm/companies/create')
            ->assertSessionHasErrors(['name', 'tax_number']);
    }

    public function test_company_show_includes_360_degree_context_and_can_attach_contacts(): void
    {
        $company = Company::factory()->create(['owner_id' => $this->admin->id]);
        $unassigned = Contact::factory()->create(['company_id' => null, 'full_name' => 'Loose Contact']);
        $attached = Contact::factory()->create(['company_id' => $company->id, 'full_name' => 'Attached Contact']);
        Deal::factory()->create(['company_id' => $company->id, 'contact_id' => $attached->id, 'value' => 45000, 'status' => 'open']);
        Quote::factory()->create(['company_id' => $company->id, 'contact_id' => $attached->id, 'quote_number' => 'CRM-COMP-1']);
        CrmTask::factory()->create([
            'taskable_type' => $company->getMorphClass(),
            'taskable_id' => $company->id,
            'title' => 'Review account',
            'completed_at' => null,
        ]);
        Activity::factory()->create([
            'activityable_type' => $company->getMorphClass(),
            'activityable_id' => $company->id,
            'subject' => 'Account updated',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.companies.show', $company))
            ->assertOk()
            ->assertSee($company->name)
            ->assertSee(__('Open Deal Value'))
            ->assertSee('Attached Contact')
            ->assertSee('CRM-COMP-1')
            ->assertSee('Review account')
            ->assertSee('Loose Contact');

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.companies.contacts.attach', $company), [
                'contact_ids' => [$unassigned->id],
            ])
            ->assertRedirect(route('crm.companies.show', $company));

        $this->assertSame($company->id, $unassigned->refresh()->company_id);
    }

    public function test_company_with_related_records_cannot_be_deleted_until_records_are_moved(): void
    {
        $company = Company::factory()->create();
        Contact::factory()->create(['company_id' => $company->id]);

        $this->actingAs($this->admin, 'admin')
            ->from(route('crm.companies.show', $company))
            ->delete(route('crm.companies.destroy', $company))
            ->assertRedirect(route('crm.companies.show', $company))
            ->assertSessionHasErrors('company');

        $this->assertNotSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_companies_can_be_bulk_deleted_when_unlinked(): void
    {
        $first = Company::factory()->create();
        $second = Company::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.companies.bulk-delete'), [
                'record_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect();

        $this->assertSoftDeleted('companies', ['id' => $first->id]);
        $this->assertSoftDeleted('companies', ['id' => $second->id]);
    }
}
