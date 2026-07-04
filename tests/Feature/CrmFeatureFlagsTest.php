<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Contact;
use App\Crm\Models\CrmWebhook;
use App\Crm\Services\Webhooks\CrmWebhookDispatcher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmFeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->owner = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_addon_features_are_disabled_by_default(): void
    {
        $this->assertFalse((bool) config('crm.features.two_factor'));
        $this->assertFalse((bool) config('crm.features.webhooks'));
        $this->assertFalse((bool) config('crm.features.calendar_feed'));
    }

    public function test_security_screen_is_hidden_when_features_are_off(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.security.index'))
            ->assertNotFound();

        $this->actingAs($this->owner, 'admin')
            ->post(route('crm.security.2fa.enable'))
            ->assertNotFound();
    }

    public function test_webhooks_screen_is_hidden_when_feature_is_off(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.webhooks.index'))
            ->assertNotFound();
    }

    public function test_calendar_feed_is_hidden_when_feature_is_off(): void
    {
        $this->owner->forceFill(['calendar_token' => Str::random(48)])->save();

        $this->get(route('crm.public.calendar.tasks', $this->owner->calendar_token))
            ->assertNotFound();
    }

    public function test_webhook_dispatcher_is_a_noop_when_feature_is_off(): void
    {
        Queue::fake();

        CrmWebhook::query()->create([
            'name' => 'Hook',
            'url' => 'https://example.test/hook',
            'secret' => 'whsec_x',
            'events' => ['contact.created'],
            'is_active' => true,
        ]);

        app(CrmWebhookDispatcher::class)->dispatch('contact.created', Contact::factory()->create());

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('crm_webhook_deliveries', 0);
    }

    public function test_sidebar_hides_disabled_feature_links(): void
    {
        $response = $this->actingAs($this->owner, 'admin')
            ->get(route('crm.dashboard'))
            ->assertOk();

        $response->assertDontSee(route('crm.webhooks.index'));
        $response->assertDontSee(route('crm.security.index'));

        config(['crm.features.webhooks' => true, 'crm.features.two_factor' => true]);

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSee(route('crm.webhooks.index'))
            ->assertSee(route('crm.security.index'));
    }
}
