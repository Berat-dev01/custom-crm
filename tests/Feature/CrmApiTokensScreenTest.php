<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\CrmApiToken;
use Tests\TestCase;

class CrmApiTokensScreenTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->owner = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_only_settings_managers_can_open_token_screen(): void
    {
        $sales = User::factory()->create()->assignRole('crm_sales');

        $this->actingAs($this->owner, 'admin')
            ->get(route('crm.api-tokens.index'))
            ->assertOk()
            ->assertSee(__('API Tokens'));

        $this->actingAs($sales, 'admin')
            ->get(route('crm.api-tokens.index'))
            ->assertForbidden();
    }

    public function test_token_can_be_created_and_plaintext_shown_once(): void
    {
        $response = $this->actingAs($this->owner, 'admin')
            ->post(route('crm.api-tokens.store'), [
                'name' => 'Zapier',
                'user_id' => $this->owner->id,
            ])
            ->assertRedirect(route('crm.api-tokens.index'))
            ->assertSessionHas('crm_api_token_plain');

        $plain = session('crm_api_token_plain');
        $this->assertStringStartsWith('crm_live_', $plain);

        $this->assertDatabaseHas('crm_api_tokens', [
            'name' => 'Zapier',
            'user_id' => $this->owner->id,
            'token_hash' => CrmApiToken::hashToken($plain),
        ]);

        // Issued token authenticates against the API.
        $this->withToken($plain)
            ->getJson('/api/crm/v1/contacts')
            ->assertOk();

        // Plaintext is flashed once and rendered on the follow-up page.
        $this->actingAs($this->owner, 'admin')
            ->withSession(['crm_api_token_plain' => $plain])
            ->get(route('crm.api-tokens.index'))
            ->assertSee($plain);
    }

    public function test_revoked_token_stops_authenticating(): void
    {
        $issued = CrmApiToken::issueFor($this->owner, 'to-revoke');

        $this->withToken($issued['plain_text_token'])
            ->getJson('/api/crm/v1/contacts')
            ->assertOk();

        $this->actingAs($this->owner, 'admin')
            ->delete(route('crm.api-tokens.destroy', $issued['token']))
            ->assertRedirect(route('crm.api-tokens.index'));

        $this->withToken($issued['plain_text_token'])
            ->getJson('/api/crm/v1/contacts')
            ->assertUnauthorized();

        $this->assertSoftDeleted('crm_api_tokens', ['id' => $issued['token']->id]);
    }

    public function test_expired_date_validation(): void
    {
        $this->actingAs($this->owner, 'admin')
            ->from(route('crm.api-tokens.index'))
            ->post(route('crm.api-tokens.store'), [
                'name' => 'Past token',
                'user_id' => $this->owner->id,
                'expires_at' => now()->subDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('expires_at');
    }
}
