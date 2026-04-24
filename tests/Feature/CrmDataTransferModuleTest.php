<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Sanalkopru\Crm\Database\Seeders\CrmDealStageSeeder;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Sanalkopru\Crm\Jobs\ProcessCrmImport;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\CrmImport;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;
use Tests\TestCase;

class CrmDataTransferModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->seed(CrmDealStageSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_import_preview_and_template_download_are_available(): void
    {
        $file = UploadedFile::fake()->createWithContent('companies.csv', implode("\n", [
            'name,email,sector',
            'Preview Co,hello@preview.test,Technology',
        ]));

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.companies.import.preview'), ['file' => $file])
            ->assertRedirect(route('crm.companies.import'))
            ->assertSessionHas('crm_import_preview');

        $preview = session('crm_import_preview');
        $this->assertSame(1, $preview['total_rows']);
        $this->assertSame('Preview Co', $preview['rows'][0]['values']['name']);
        $this->assertTrue($preview['rows'][0]['valid']);
        $this->assertSame(1, $preview['summary']['valid_rows']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('crm.companies.template'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString('name,email,phone', $response->streamedContent());
    }

    public function test_ajax_import_preview_renders_inside_scrollable_table_container(): void
    {
        $file = UploadedFile::fake()->createWithContent('contacts.csv', implode("\n", [
            'full_name,first_name,last_name,email,phone,title,company,lifecycle_stage,source,owner_email,tags',
            'Ada Lovelace,Ada,Lovelace,ada@example.com,+905551112233,CTO,Acme A.S.,lead,website,crm.owner@example.com,VIP|Enterprise',
        ]));

        $this->actingAs($this->admin, 'admin')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('crm.contacts.import.preview'), ['file' => $file])
            ->assertOk()
            ->assertSee('crm-import-preview-table', false)
            ->assertSee('Ada Lovelace', false);
    }

    public function test_contacts_import_uses_defaults_when_optional_columns_are_missing(): void
    {
        $file = UploadedFile::fake()->createWithContent('contacts.csv', implode("\n", [
            'full_name,email',
            'Missing Lifecycle,missing-lifecycle@example.com',
        ]));

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.contacts.import.store'), ['file' => $file])
            ->assertRedirect(route('crm.contacts.import'))
            ->assertSessionHas('crm_import_result');

        $this->assertDatabaseHas('contacts', [
            'full_name' => 'Missing Lifecycle',
            'email' => 'missing-lifecycle@example.com',
            'lifecycle_stage' => 'lead',
        ]);
    }

    public function test_companies_import_records_validation_errors_and_downloadable_report(): void
    {
        Company::factory()->create(['name' => 'Existing Co', 'tax_number' => 'DUP-1']);
        $file = UploadedFile::fake()->createWithContent('companies.csv', implode("\n", [
            'name,email,phone,website,tax_number,sector,city,country',
            'Valid Co,hello@valid.test,+905551112233,https://valid.test,TR-100,Technology,Istanbul,TR',
            'Broken Co,not-an-email,+905551112233,https://broken.test,TR-101,Technology,Istanbul,TR',
            'Existing Co,hello@existing.test,+905551112233,https://existing.test,DUP-1,Technology,Istanbul,TR',
        ]));

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.companies.import.store'), ['file' => $file])
            ->assertRedirect(route('crm.companies.import'))
            ->assertSessionHas('crm_import_result');

        $this->assertDatabaseHas('companies', ['name' => 'Valid Co']);
        $this->assertDatabaseMissing('companies', ['email' => 'not-an-email']);
        $this->assertDatabaseHas('crm_imports', [
            'module' => 'companies',
            'status' => 'completed_with_errors',
            'processed_rows' => 1,
            'failed_rows' => 2,
        ]);

        $result = session('crm_import_result');
        $this->assertSame(1, $result['created']);
        $this->assertSame(2, $result['failed']);
        $this->assertNotEmpty($result['error_report_url']);

        $reportResponse = $this->actingAs($this->admin, 'admin')
            ->get($result['error_report_url'])
            ->assertOk();

        $this->assertStringContainsString('not-an-email', $reportResponse->streamedContent());
    }

    public function test_deals_import_resolves_relations_and_export_respects_filters(): void
    {
        $stage = DealStage::query()->where('slug', 'quote-sent')->firstOrFail();
        $company = Company::factory()->create(['name' => 'Import Account']);
        $contact = Contact::factory()->create(['company_id' => $company->id, 'email' => 'buyer@example.com']);
        Deal::factory()->create(['title' => 'Hidden Deal', 'status' => 'lost', 'value' => 100]);

        $file = UploadedFile::fake()->createWithContent('deals.csv', implode("\n", [
            'title,company,contact_email,stage,value,currency,probability,expected_close_date,status,lost_reason,owner_email',
            'Imported Deal,Import Account,buyer@example.com,'.$stage->name.',5000,TRY,70,2026-06-10,open,,'.$this->admin->email,
        ]));

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.deals.import.store'), ['file' => $file])
            ->assertRedirect(route('crm.deals.import'));

        $deal = Deal::query()->where('title', 'Imported Deal')->firstOrFail();
        $this->assertSame($company->id, $deal->company_id);
        $this->assertSame($contact->id, $deal->contact_id);
        $this->assertSame($stage->id, $deal->stage_id);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('crm.deals.export', ['status' => 'open']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Imported Deal', $csv);
        $this->assertStringNotContainsString('Hidden Deal', $csv);
        $this->assertDatabaseHas('crm_exports', [
            'module' => 'deals',
            'status' => 'completed',
            'total_rows' => 1,
        ]);
    }

    public function test_quotes_export_is_available_but_viewer_without_export_permission_is_blocked(): void
    {
        $quote = Quote::factory()->create(['quote_number' => 'CRM-EXPORT-1']);
        $viewer = User::factory()->create()->assignRole('crm_viewer');

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('crm.quotes.export'))
            ->assertOk();

        $this->assertStringContainsString($quote->quote_number, $response->streamedContent());

        $this->actingAs($viewer, 'admin')
            ->get(route('crm.quotes.export'))
            ->assertForbidden();
    }

    public function test_large_imports_are_queued(): void
    {
        Queue::fake();
        config(['crm.data_transfer.queue_threshold' => 1]);

        $file = UploadedFile::fake()->createWithContent('contacts.csv', implode("\n", [
            'full_name,email,lifecycle_stage',
            'First Queued,first-queued@example.com,lead',
            'Second Queued,second-queued@example.com,lead',
        ]));

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.contacts.import.store'), ['file' => $file])
            ->assertRedirect(route('crm.contacts.import'))
            ->assertSessionHas('crm_import_result');

        Queue::assertPushed(ProcessCrmImport::class);
        $this->assertDatabaseHas('crm_imports', [
            'module' => 'contacts',
            'status' => 'pending',
            'total_rows' => 2,
        ]);

        $result = session('crm_import_result');
        $this->assertTrue($result['queued']);
        $this->assertSame(0, CrmImport::query()->firstOrFail()->processed_rows);
    }
}
