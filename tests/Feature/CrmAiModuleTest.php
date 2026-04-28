<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sanalkopru\Crm\Contracts\AiProviderContract;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\QuoteItem;
use Tests\TestCase;

class CrmAiModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private FakeAiProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
        $this->provider = new FakeAiProvider;
        $this->app->instance(AiProviderContract::class, $this->provider);

        config([
            'crm.ai.enabled' => true,
            'crm.ai.driver' => 'openai',
            'crm.ai.drivers.openai.api_key' => 'test-key',
        ]);
    }

    public function test_summarize_endpoint_uses_provider_without_sensitive_contact_fields(): void
    {
        $activity = Activity::factory()->create([
            'subject' => 'Pricing call',
            'body' => 'Customer asked for approval timing.',
            'type' => 'call',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('crm.ai.summarize'), [
                'type' => 'note',
                'activity_id' => $activity->id,
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'summary' => 'summary draft',
            ]);

        $this->assertSame('summarize', $this->provider->lastMethod);
        $this->assertStringContainsString('Pricing call', $this->provider->lastContent);
        $this->assertSame('call', $this->provider->lastContext['crm']['activity_type']);
    }

    public function test_draft_email_and_lost_deal_analysis_work_as_drafts(): void
    {
        $stage = DealStage::factory()->lost()->create();
        $company = Company::factory()->create(['name' => 'Acme CRM']);
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'full_name' => 'Sensitive Person',
            'email' => 'sensitive@example.test',
            'phone' => '+90 555 111 22 33',
        ]);
        $deal = Deal::factory()->lost()->create([
            'stage_id' => $stage->id,
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'title' => 'Enterprise CRM',
            'lost_reason' => 'No budget this quarter.',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('crm.ai.draft-email'), [
                'deal_id' => $deal->id,
                'brief' => 'Ask for a next meeting.',
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'draft' => 'email draft',
            ]);

        $this->assertSame('draftEmail', $this->provider->lastMethod);
        $this->assertSame('Sensitive Person', $this->provider->lastContext['crm']['deal']['contact_name']);
        $this->assertArrayNotHasKey('email', $this->provider->lastContext['crm']['deal']);
        $this->assertArrayNotHasKey('phone', $this->provider->lastContext['crm']['deal']);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('crm.ai.summarize'), [
                'type' => 'lost_deal',
                'deal_id' => $deal->id,
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'summary' => 'lost analysis',
            ]);

        $this->assertSame('analyzeLostDeal', $this->provider->lastMethod);
    }

    public function test_quote_follow_up_endpoint_uses_quote_context(): void
    {
        $quote = Quote::factory()->create(['quote_number' => 'CRM-AI-001', 'status' => 'sent']);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'name' => 'CRM Setup']);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('crm.ai.follow-up'), [
                'quote_id' => $quote->id,
                'brief' => 'Follow up politely.',
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'draft' => 'follow-up draft',
            ]);

        $this->assertSame('draftFollowUp', $this->provider->lastMethod);
        $this->assertSame('CRM-AI-001', $this->provider->lastContext['crm']['quote']['quote_number']);
        $this->assertSame('CRM Setup', $this->provider->lastContext['crm']['quote']['items'][0]['name']);
    }

    public function test_ai_disabled_returns_friendly_response_without_calling_provider(): void
    {
        config(['crm.ai.enabled' => false]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('crm.ai.draft-email'), ['brief' => 'Draft this.'])
            ->assertAccepted()
            ->assertJson([
                'ok' => false,
                'draft' => null,
                'message' => trans('crm::messages.ai.not_configured'),
            ]);

        $this->assertNull($this->provider->lastMethod);
    }

    public function test_ai_provider_failure_returns_friendly_error(): void
    {
        $this->provider->fail = true;

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('crm.ai.summarize'), [
                'type' => 'note',
                'content' => 'Summarize this note.',
            ])
            ->assertStatus(503)
            ->assertJson([
                'ok' => false,
                'summary' => null,
                'message' => trans('crm::messages.ai.request_failed_retry'),
            ]);
    }

    public function test_form_submission_stores_ai_draft_in_session(): void
    {
        $deal = Deal::factory()->create(['title' => 'Session Draft Deal']);

        $this->actingAs($this->admin, 'admin')
            ->from(route('crm.deals.show', $deal))
            ->post(route('crm.ai.draft-email'), [
                'deal_id' => $deal->id,
                'brief' => 'Draft from form.',
            ])
            ->assertRedirect(route('crm.deals.show', $deal))
            ->assertSessionHas('crm_ai_draft', 'email draft');
    }
}

class FakeAiProvider implements AiProviderContract
{
    public ?string $lastMethod = null;

    public string $lastContent = '';

    /**
     * @var array<string, mixed>
     */
    public array $lastContext = [];

    public bool $fail = false;

    public function summarize(string $content, array $context = []): string
    {
        return $this->record('summarize', $content, $context, 'summary draft');
    }

    public function draftEmail(string $brief, array $context = []): string
    {
        return $this->record('draftEmail', $brief, $context, 'email draft');
    }

    public function draftFollowUp(string $brief, array $context = []): string
    {
        return $this->record('draftFollowUp', $brief, $context, 'follow-up draft');
    }

    public function analyzeLostDeal(string $brief, array $context = []): string
    {
        return $this->record('analyzeLostDeal', $brief, $context, 'lost analysis');
    }

    private function record(string $method, string $content, array $context, string $response): string
    {
        if ($this->fail) {
            throw new \RuntimeException('AI failed.');
        }

        $this->lastMethod = $method;
        $this->lastContent = $content;
        $this->lastContext = $context;

        return $response;
    }
}
