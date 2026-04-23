<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\SavedFilter;
use Sanalkopru\Crm\Models\Tag;
use Tests\TestCase;

class CrmTagsSavedFiltersModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_tag_can_be_created_updated_and_deleted(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('crm.tags.store'), [
                'name' => 'Strategic Account',
                'slug' => '',
                'color' => '#2563eb',
            ]);

        $tag = Tag::query()->where('slug', 'strategic-account')->firstOrFail();

        $response->assertRedirect(route('crm.tags.show', $tag));

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.tags.update', $tag), [
                'name' => 'Strategic Customer',
                'slug' => 'strategic-customer',
                'color' => '#16a34a',
            ])
            ->assertRedirect(route('crm.tags.show', $tag));

        $tag->refresh();
        $this->assertSame('Strategic Customer', $tag->name);
        $this->assertSame('strategic-customer', $tag->slug);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('crm.tags.destroy', $tag))
            ->assertRedirect(route('crm.tags.index'));

        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
    }

    public function test_contact_company_and_deal_lists_filter_by_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'VIP', 'slug' => 'vip']);
        $otherTag = Tag::factory()->create(['name' => 'Cold', 'slug' => 'cold']);
        $contact = Contact::factory()->create(['full_name' => 'Tagged Contact']);
        $otherContact = Contact::factory()->create(['full_name' => 'Other Contact']);
        $company = Company::factory()->create(['name' => 'Tagged Company']);
        $otherCompany = Company::factory()->create(['name' => 'Other Company']);
        $stage = DealStage::factory()->create(['is_won' => false, 'is_lost' => false]);
        $deal = Deal::factory()->create(['title' => 'Tagged Deal', 'stage_id' => $stage->id]);
        $otherDeal = Deal::factory()->create(['title' => 'Other Deal', 'stage_id' => $stage->id]);

        $contact->tags()->attach($tag);
        $otherContact->tags()->attach($otherTag);
        $company->tags()->attach($tag);
        $otherCompany->tags()->attach($otherTag);
        $deal->tags()->attach($tag);
        $otherDeal->tags()->attach($otherTag);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.contacts.index', ['tag_id' => $tag->id]))
            ->assertOk()
            ->assertSee('Tagged Contact')
            ->assertDontSee('Other Contact');

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.companies.index', ['tag_id' => $tag->id]))
            ->assertOk()
            ->assertSee('Tagged Company')
            ->assertDontSee('Other Company');

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.deals.index', ['view' => 'list', 'tag_id' => $tag->id]))
            ->assertOk()
            ->assertSee('Tagged Deal')
            ->assertDontSee('Other Deal');
    }

    public function test_bulk_tag_action_can_attach_and_remove_tags(): void
    {
        $tag = Tag::factory()->create();
        $stage = DealStage::factory()->create(['is_won' => false, 'is_lost' => false]);
        $dealA = Deal::factory()->create(['stage_id' => $stage->id]);
        $dealB = Deal::factory()->create(['stage_id' => $stage->id]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.tags.bulk'), [
                'taggable_type' => 'deal',
                'record_ids' => [$dealA->id, $dealB->id],
                'tag_ids' => [$tag->id],
                'mode' => 'attach',
            ])
            ->assertRedirect();

        $this->assertTrue($dealA->refresh()->tags->contains($tag));
        $this->assertTrue($dealB->refresh()->tags->contains($tag));

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.tags.bulk'), [
                'taggable_type' => 'deal',
                'record_ids' => [$dealA->id, $dealB->id],
                'tag_ids' => [$tag->id],
                'mode' => 'detach',
            ])
            ->assertRedirect();

        $this->assertFalse($dealA->refresh()->tags->contains($tag));
        $this->assertFalse($dealB->refresh()->tags->contains($tag));
    }

    public function test_saved_filter_can_be_saved_and_reapplied(): void
    {
        $tag = Tag::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.saved-filters.store'), [
                'module' => 'contacts',
                'name' => 'VIP Contacts',
                'visibility' => 'private',
                'filters' => [
                    'search' => 'Acme',
                    'tag_id' => $tag->id,
                    'sort' => 'full_name',
                    'direction' => 'asc',
                ],
            ])
            ->assertRedirect();

        $savedFilter = SavedFilter::query()->where('name', 'VIP Contacts')->firstOrFail();
        $this->assertSame('contacts', $savedFilter->module);
        $this->assertSame((string) $tag->id, (string) $savedFilter->filters['tag_id']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.saved-filters.apply', $savedFilter))
            ->assertRedirect(route('crm.contacts.index', $savedFilter->filters));
    }

    public function test_saved_filters_support_tasks_quotes_and_activities_modules(): void
    {
        $modules = [
            'tasks' => ['status' => 'open', 'scope' => 'my'],
            'quotes' => ['status' => 'draft'],
            'activities' => ['type' => 'note'],
        ];

        foreach ($modules as $module => $filters) {
            $this->actingAs($this->admin, 'admin')
                ->post(route('crm.saved-filters.store'), [
                    'module' => $module,
                    'name' => strtoupper($module).' Filter',
                    'visibility' => 'private',
                    'filters' => $filters,
                ])
                ->assertRedirect();

            $savedFilter = SavedFilter::query()->where('module', $module)->latest('id')->firstOrFail();

            $this->assertSame($module, $savedFilter->module);
            $this->assertSame($filters, $savedFilter->filters);

            $this->actingAs($this->admin, 'admin')
                ->get(route('crm.saved-filters.apply', $savedFilter))
                ->assertRedirect(route("crm.{$module}.index", $filters));
        }
    }

    public function test_private_saved_filter_is_not_visible_to_other_users(): void
    {
        $savedFilter = SavedFilter::query()->create([
            'name' => 'Private Filter',
            'module' => 'deals',
            'filters' => ['status' => 'open'],
            'visibility' => 'private',
            'user_id' => $this->admin->id,
        ]);
        $other = User::factory()->create()->assignRole('crm_owner');

        $this->actingAs($other, 'admin')
            ->get(route('crm.saved-filters.apply', $savedFilter))
            ->assertForbidden();
    }
}
