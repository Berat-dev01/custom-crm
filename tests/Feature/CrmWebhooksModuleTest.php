<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Jobs\SendCrmWebhook;
use App\Crm\Models\Contact;
use App\Crm\Models\CrmWebhook;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use Tests\TestCase;

class CrmWebhooksModuleTest extends TestCase
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

    private function makeWebhook(array $events, bool $active = true): CrmWebhook
    {
        return CrmWebhook::query()->create([
            'name' => 'Test hook',
            'url' => 'https://hooks.example.test/crm',
            'secret' => 'whsec_testsecret',
            'events' => $events,
            'is_active' => $active,
        ]);
    }

    public function test_webhook_screen_requires_settings_permission(): void
    {
        $sales = User::factory()->create()->assignRole('crm_sales');

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.webhooks.index'))
            ->assertOk()
            ->assertSee(__('Webhooks'));

        $this->actingAs($sales, 'admin')
            ->get(route('crm.webhooks.index'))
            ->assertForbidden();
    }

    public function test_webhook_can_be_created_with_secret_shown_once(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->post(route('crm.webhooks.store'), [
                'name' => 'Zapier',
                'url' => 'https://hooks.zapier.com/x',
                'events' => ['deal.won', 'contact.created'],
            ])
            ->assertRedirect(route('crm.webhooks.index'))
            ->assertSessionHas('crm_webhook_secret');

        $this->assertDatabaseHas('crm_webhooks', ['name' => 'Zapier', 'is_active' => true]);
        $this->assertStringStartsWith('whsec_', session('crm_webhook_secret'));
    }

    public function test_deal_won_queues_delivery_for_subscribed_webhooks_only(): void
    {
        Queue::fake();

        $subscribed = $this->makeWebhook(['deal.won']);
        $this->makeWebhook(['contact.created']); // different event
        $this->makeWebhook(['deal.won'], active: false); // paused

        $openStage = DealStage::query()->where('is_won', false)->where('is_lost', false)->ordered()->firstOrFail();
        $deal = Deal::factory()->create(['stage_id' => $openStage->id, 'status' => 'open']);

        $this->actingAs($this->owner, 'admin')
            ->patch(route('crm.deals.close-won', $deal))
            ->assertRedirect();

        Queue::assertPushed(SendCrmWebhook::class, 1);

        $this->assertDatabaseHas('crm_webhook_deliveries', [
            'webhook_id' => $subscribed->id,
            'event' => 'deal.won',
            'status' => 'pending',
        ]);
        $this->assertSame(1, \App\Crm\Models\CrmWebhookDelivery::query()->count());
    }

    public function test_contact_created_dispatches_webhook(): void
    {
        Queue::fake();
        $webhook = $this->makeWebhook(['contact.created']);

        $this->actingAs($this->owner, 'admin')
            ->post(route('crm.contacts.store'), [
                'first_name' => 'Hook',
                'last_name' => 'Test',
                'lifecycle_stage' => 'lead',
            ])
            ->assertRedirect();

        Queue::assertPushed(SendCrmWebhook::class, 1);

        $delivery = \App\Crm\Models\CrmWebhookDelivery::query()->firstOrFail();
        $this->assertSame('contact.created', $delivery->event);
        $this->assertSame('contact', $delivery->payload['data']['type']);
        $this->assertSame('Hook Test', $delivery->payload['data']['full_name']);
    }

    public function test_delivery_job_sends_signed_request_and_records_result(): void
    {
        Http::fake(['hooks.example.test/*' => Http::response(['ok' => true], 200)]);

        $webhook = $this->makeWebhook(['contact.created']);
        $contact = Contact::factory()->create();

        $delivery = $webhook->deliveries()->create([
            'event' => 'contact.created',
            'payload' => ['event' => 'contact.created', 'data' => ['id' => $contact->id]],
            'status' => 'pending',
        ]);

        (new SendCrmWebhook($delivery))->handle();

        $delivery->refresh();
        $this->assertSame('success', $delivery->status);
        $this->assertSame(200, $delivery->response_status);
        $this->assertSame(1, $delivery->attempts);

        Http::assertSent(function ($request) use ($webhook, $delivery): bool {
            $expected = hash_hmac('sha256', $request->body(), $webhook->secret);

            return $request->url() === $webhook->url
                && $request->header('X-CRM-Event')[0] === 'contact.created'
                && $request->header('X-CRM-Signature')[0] === $expected
                && $request->header('X-CRM-Delivery')[0] === $delivery->public_id;
        });
    }

    public function test_paused_webhook_skips_delivery_job(): void
    {
        Http::fake();

        $webhook = $this->makeWebhook(['contact.created'], active: false);
        $delivery = $webhook->deliveries()->create([
            'event' => 'contact.created',
            'payload' => ['event' => 'contact.created', 'data' => []],
            'status' => 'pending',
        ]);

        (new SendCrmWebhook($delivery))->handle();

        Http::assertNothingSent();
        $this->assertSame('pending', $delivery->refresh()->status);
    }
}
