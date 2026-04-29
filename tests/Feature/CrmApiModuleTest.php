<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\CrmApiToken;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use App\Crm\Models\Task;
use Tests\TestCase;

class CrmApiModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private string $ownerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->owner = User::factory()->create()->assignRole('crm_owner');
        $this->ownerToken = CrmApiToken::issueFor($this->owner, 'feature-test')['plain_text_token'];
    }

    public function test_crm_api_routes_are_registered(): void
    {
        foreach ($this->routeNames() as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing route [{$routeName}].");
        }
    }

    public function test_protected_api_requires_bearer_token(): void
    {
        Contact::factory()->create();

        $this->getJson('/api/crm/contacts')
            ->assertUnauthorized()
            ->assertJsonPath('message', trans('crm::messages.api.unauthenticated'));
    }

    public function test_bearer_token_can_list_and_create_contacts(): void
    {
        Contact::factory()->create(['full_name' => 'Ada Api']);

        $this->withToken($this->ownerToken)
            ->getJson('/api/crm/contacts?search=Ada')
            ->assertOk()
            ->assertJsonPath('data.0.full_name', 'Ada Api');

        $company = Company::factory()->create();

        $this->withToken($this->ownerToken)
            ->postJson('/api/crm/contacts', [
                'first_name' => 'API',
                'last_name' => 'Contact',
                'email' => 'api.contact@example.test',
                'company_id' => $company->id,
                'lifecycle_stage' => 'lead',
                'source' => 'website',
                'owner_id' => $this->owner->id,
            ])
            ->assertCreated()
            ->assertJsonPath('message', trans('crm::messages.contacts.created'))
            ->assertJsonPath('data.full_name', 'API Contact');

        $this->assertDatabaseHas('contacts', [
            'email' => 'api.contact@example.test',
            'created_by' => $this->owner->id,
        ]);
        $this->assertNotNull(CrmApiToken::query()->first()?->last_used_at);
    }

    public function test_api_policies_return_forbidden_for_missing_permission(): void
    {
        $viewer = User::factory()->create()->assignRole('crm_viewer');
        $token = CrmApiToken::issueFor($viewer, 'viewer-test')['plain_text_token'];

        $this->withToken($token)
            ->postJson('/api/crm/contacts', [
                'full_name' => 'Forbidden Contact',
                'lifecycle_stage' => 'lead',
            ])
            ->assertForbidden();
    }

    public function test_validation_errors_are_consistent_json(): void
    {
        $this->withToken($this->ownerToken)
            ->postJson('/api/crm/companies', [
                'email' => 'not-an-email',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_deal_move_and_task_complete_use_action_layer(): void
    {
        $sourceStage = DealStage::factory()->create([
            'name' => 'API Source',
            'slug' => 'api-source',
            'position' => 1,
            'probability' => 20,
            'is_won' => false,
            'is_lost' => false,
        ]);
        $targetStage = DealStage::factory()->create([
            'name' => 'API Target',
            'slug' => 'api-target',
            'position' => 2,
            'probability' => 70,
            'is_won' => false,
            'is_lost' => false,
        ]);
        $deal = Deal::factory()->create([
            'stage_id' => $sourceStage->id,
            'status' => 'open',
            'probability' => 20,
            'position' => 1,
        ]);
        $task = Task::factory()->create(['status' => 'open', 'completed_at' => null]);

        $this->withToken($this->ownerToken)
            ->postJson("/api/crm/deals/{$deal->id}/move", [
                'stage_id' => $targetStage->id,
                'position' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('message', trans('crm::messages.deals.moved'))
            ->assertJsonPath('data.stage_id', $targetStage->id)
            ->assertJsonPath('data.probability', 70);

        $this->withToken($this->ownerToken)
            ->postJson("/api/crm/tasks/{$task->id}/complete")
            ->assertOk()
            ->assertJsonPath('message', trans('crm::messages.tasks.completed'))
            ->assertJsonPath('data.status', 'completed');

        $this->assertNotNull($task->refresh()->completed_at);
    }

    public function test_quote_api_creates_calculated_quote_with_items(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);

        $this->withToken($this->ownerToken)
            ->postJson('/api/crm/quotes', [
                'company_id' => $company->id,
                'contact_id' => $contact->id,
                'currency' => 'TRY',
                'items' => [
                    [
                        'name' => 'API License',
                        'quantity' => 2,
                        'unit_price' => 1000,
                        'tax_rate' => 20,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('message', trans('crm::messages.quotes.created'))
            ->assertJsonPath('data.company_id', $company->id)
            ->assertJsonPath('data.subtotal', 2000)
            ->assertJsonPath('data.grand_total', 2400)
            ->assertJsonPath('data.items.0.name', 'API License');

        $quote = Quote::query()->firstOrFail();

        $this->withToken($this->ownerToken)
            ->getJson("/api/crm/quotes/{$quote->id}")
            ->assertOk()
            ->assertJsonPath('data.items.0.line_total', 2400);
    }

    /**
     * @return list<string>
     */
    private function routeNames(): array
    {
        return [
            'crm.api.health',
            'crm.api.contacts.index',
            'crm.api.contacts.store',
            'crm.api.contacts.show',
            'crm.api.contacts.update',
            'crm.api.companies.index',
            'crm.api.companies.store',
            'crm.api.companies.show',
            'crm.api.companies.update',
            'crm.api.deals.index',
            'crm.api.deals.store',
            'crm.api.deals.show',
            'crm.api.deals.update',
            'crm.api.deals.move',
            'crm.api.tasks.index',
            'crm.api.tasks.store',
            'crm.api.tasks.show',
            'crm.api.tasks.update',
            'crm.api.tasks.complete',
            'crm.api.quotes.index',
            'crm.api.quotes.store',
            'crm.api.quotes.show',
            'crm.api.quotes.update',
        ];
    }
}
