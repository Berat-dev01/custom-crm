<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\CrmAuditLog;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use Tests\TestCase;

class CrmAuditSecurityModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->seed(CrmDealStageSeeder::class);
        $this->owner = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_contact_create_update_and_delete_are_audited_without_sensitive_values(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->post(route('crm.contacts.store'), [
                'full_name' => 'Audit Contact',
                'email' => 'audit.contact@example.test',
                'phone' => '+90 555 000 00 00',
                'lifecycle_stage' => 'lead',
            ])
            ->assertRedirect();

        $contact = Contact::query()->where('full_name', 'Audit Contact')->firstOrFail();
        $created = CrmAuditLog::query()->where('event', 'crm.contact.created')->firstOrFail();

        $this->assertSame($contact->id, $created->auditable_id);
        $this->assertSame('[redacted]', data_get($created->new_values, 'email'));
        $this->assertSame('[redacted]', data_get($created->new_values, 'phone'));

        $this->actingAs($this->owner, 'admin')
            ->put(route('crm.contacts.update', $contact), [
                'full_name' => 'Audit Contact Updated',
                'email' => 'new.audit.contact@example.test',
                'phone' => '+90 555 111 11 11',
                'lifecycle_stage' => 'customer',
            ])
            ->assertRedirect();

        $this->actingAs($this->owner, 'admin')
            ->delete(route('crm.contacts.destroy', $contact))
            ->assertRedirect();

        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'crm.contact.updated']);
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'crm.contact.deleted']);
    }

    public function test_deal_and_quote_status_transitions_are_audited(): void
    {
        $sourceStage = DealStage::query()->where('slug', 'new')->firstOrFail();
        $wonStage = DealStage::query()->where('is_won', true)->firstOrFail();
        $lostStage = DealStage::query()->where('is_lost', true)->firstOrFail();

        $deal = Deal::factory()->create([
            'stage_id' => $sourceStage->id,
            'status' => 'open',
            'probability' => $sourceStage->probability,
        ]);
        $quote = Quote::factory()->create(['status' => 'draft']);
        $rejectedQuote = Quote::factory()->create(['status' => 'sent']);

        $this->actingAs($this->owner, 'admin')
            ->patchJson(route('crm.deals.move', $deal), [
                'stage_id' => $wonStage->id,
                'position' => 1,
            ])
            ->assertOk();

        $this->actingAs($this->owner, 'admin')
            ->patchJson(route('crm.deals.move', $deal->refresh()), [
                'stage_id' => $lostStage->id,
                'position' => 1,
                'lost_reason' => 'Security audit test',
            ])
            ->assertOk();

        $this->actingAs($this->owner, 'admin')
            ->patch(route('crm.quotes.send', $quote))
            ->assertRedirect();

        $this->actingAs($this->owner, 'admin')
            ->patch(route('crm.quotes.accept', $quote->refresh()), ['mark_deal_won' => false])
            ->assertRedirect();

        $this->actingAs($this->owner, 'admin')
            ->patch(route('crm.quotes.reject', $rejectedQuote))
            ->assertRedirect();

        foreach (['crm.deal.won', 'crm.deal.lost', 'crm.quote.sent', 'crm.quote.accepted', 'crm.quote.rejected'] as $event) {
            $this->assertDatabaseHas('crm_audit_logs', ['event' => $event]);
        }
    }

    public function test_settings_import_and_export_are_audited(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->put(route('crm.settings.update'), [
                'company_name' => 'Audit CRM',
                'company_email' => 'audit@example.test',
                'default_currency' => 'TRY',
                'default_tax_rate' => '20',
                'quote_prefix' => 'AUD-',
                'notify_task_reminders' => '1',
                'notify_quote_status_changes' => '1',
                'ai_enabled' => '0',
                'ai_driver' => 'openai',
            ])
            ->assertRedirect();

        $file = UploadedFile::fake()->createWithContent(
            'companies.csv',
            "name,email,website\nAudit Import Co,import@example.test,https://import.example.test\n"
        );

        $this->actingAs($this->owner, 'admin')
            ->post(route('crm.companies.import.store'), ['file' => $file])
            ->assertRedirect();

        Company::factory()->create(['name' => 'Audit Export Co']);

        $this->actingAs($this->owner, 'admin')
            ->post(route('crm.companies.export'))
            ->assertOk();

        foreach (['crm.settings.changed', 'crm.import.started', 'crm.export.started'] as $event) {
            $this->assertDatabaseHas('crm_audit_logs', ['event' => $event]);
        }

        $settingsLog = CrmAuditLog::query()->where('event', 'crm.settings.changed')->firstOrFail();
        $this->assertSame('[redacted]', data_get($settingsLog->new_values, 'company_email'));
    }

    public function test_import_upload_and_ai_rate_limit_security_controls_work(): void
    {
        $unsafeFile = UploadedFile::fake()->createWithContent('payload.svg', '<svg></svg>');

        $this->actingAs($this->owner, 'admin')
            ->post(route('crm.companies.import.preview'), ['file' => $unsafeFile])
            ->assertSessionHasErrors('file');

        config(['crm.ai.rate_limit_per_minute' => 1]);
        RateLimiter::clear('crm-ai:'.$this->owner->id);

        $this->actingAs($this->owner, 'admin')
            ->postJson(route('crm.ai.summarize-note'))
            ->assertAccepted();

        $this->actingAs($this->owner, 'admin')
            ->postJson(route('crm.ai.summarize-note'))
            ->assertTooManyRequests();
    }

    public function test_api_and_ai_routes_have_security_middleware(): void
    {
        $apiMiddleware = Route::getRoutes()->getByName('crm.api.contacts.index')->gatherMiddleware();
        $aiMiddleware = Route::getRoutes()->getByName('crm.ai.summarize-note')->gatherMiddleware();

        $this->assertContains('crm.api.auth', $apiMiddleware);
        $this->assertContains('throttle:crm-api', $apiMiddleware);
        $this->assertContains('throttle:crm-ai', $aiMiddleware);
    }
}
