<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Jobs\ProcessCrmImport;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\CrmImport;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use App\Crm\Notifications\ImportStatusNotification;
use App\Crm\Services\DataTransfer\CrmDataTransferService;
use App\Crm\Support\CrmLabelCatalog;
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
            ->post(route('crm.deals.export'), ['status' => 'open'])
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
            ->post(route('crm.quotes.export'))
            ->assertOk();

        $this->assertStringContainsString($quote->quote_number, $response->streamedContent());

        $this->actingAs($viewer, 'admin')
            ->post(route('crm.quotes.export'))
            ->assertForbidden();
    }

    public function test_large_imports_are_queued(): void
    {
        Queue::fake();
        Notification::fake();
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
        Notification::assertSentTo(
            $this->admin,
            ImportStatusNotification::class,
            function (ImportStatusNotification $notification) use ($file): bool {
                $data = $notification->toArray($this->admin);

                return $notification->status === 'queued'
                    && $data['title'] === trans('crm::notifications.import_status.queued_title', [
                        'module' => app(CrmLabelCatalog::class)->moduleLabel('contacts'),
                    ])
                    && $data['body'] === trans('crm::notifications.import_status.queued_body', [
                        'filename' => $file->getClientOriginalName(),
                    ]);
            }
        );

        $result = session('crm_import_result');
        $this->assertTrue($result['queued']);
        $this->assertSame(0, CrmImport::query()->firstOrFail()->processed_rows);
    }

    public function test_processed_import_sends_completion_notification_to_creator(): void
    {
        Notification::fake();

        $file = UploadedFile::fake()->createWithContent('companies.csv', implode("\n", [
            'name,email,phone,website,tax_number,sector,city,country',
            'Valid Queue Co,hello@queue-valid.test,+905551112233,https://queue-valid.test,TR-200,Technology,Istanbul,TR',
            'Broken Queue Co,not-an-email,+905551112233,https://queue-broken.test,TR-201,Technology,Istanbul,TR',
        ]));

        config(['crm.data_transfer.queue_threshold' => 1]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.companies.import.store'), ['file' => $file])
            ->assertRedirect(route('crm.companies.import'));

        $import = CrmImport::query()->latest('id')->firstOrFail();

        app(CrmDataTransferService::class)->process($import, $this->admin);

        Notification::assertSentTo(
            $this->admin,
            ImportStatusNotification::class,
            function (ImportStatusNotification $notification) use ($import): bool {
                $data = $notification->toArray($this->admin);

                return $notification->import->is($import->fresh())
                    && $notification->status === 'completed_with_errors'
                    && $data['title'] === trans('crm::notifications.import_status.completed_with_errors_title', [
                        'module' => app(CrmLabelCatalog::class)->moduleLabel('companies'),
                    ])
                    && $data['body'] === trans('crm::notifications.import_status.completed_body', [
                        'created' => 1,
                        'failed' => 1,
                    ]);
            }
        );
    }

    public function test_import_status_notifications_can_be_disabled_from_settings(): void
    {
        Queue::fake();
        Notification::fake();
        config(['crm.data_transfer.queue_threshold' => 1]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.settings.update'), [
                'company_name' => 'Import Prefs Co',
                'default_currency' => 'TRY',
                'default_tax_rate' => '20',
                'quote_prefix' => 'CRM-',
                'quote_terms' => '',
                'notify_task_reminders' => '1',
                'notify_task_assignments' => '1',
                'notify_quote_status_changes' => '1',
                'notify_import_status_updates' => '0',
                'ai_enabled' => '0',
                'ai_driver' => 'openai',
                'ai_model' => '',
            ])
            ->assertRedirect(route('crm.settings.index'));

        $file = UploadedFile::fake()->createWithContent('contacts.csv', implode("\n", [
            'full_name,email,lifecycle_stage',
            'First Silent,first-silent@example.com,lead',
            'Second Silent,second-silent@example.com,lead',
        ]));

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.contacts.import.store'), ['file' => $file])
            ->assertRedirect(route('crm.contacts.import'));

        Notification::assertNothingSent();
    }
}
