<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Crm\Models\Activity;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use App\Crm\Models\QuoteItem;
use App\Crm\Models\Tag;
use App\Crm\Models\Task as CrmTask;
use Tests\TestCase;

class CrmModelLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_create_a_connected_crm_graph(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create(['owner_id' => $owner->id]);
        $contact = Contact::factory()->create([
            'company_id' => $company->id,
            'owner_id' => $owner->id,
        ]);
        $stage = DealStage::factory()->create(['slug' => 'proposal']);
        $deal = Deal::factory()->create([
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'stage_id' => $stage->id,
            'owner_id' => $owner->id,
        ]);
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'deal_id' => $deal->id,
            'owner_id' => $owner->id,
        ]);
        $item = QuoteItem::factory()->create(['quote_id' => $quote->id]);
        $task = CrmTask::factory()->create([
            'taskable_type' => $deal->getMorphClass(),
            'taskable_id' => $deal->id,
            'assigned_to' => $owner->id,
        ]);
        $activity = Activity::factory()->create([
            'activityable_type' => $contact->getMorphClass(),
            'activityable_id' => $contact->id,
            'user_id' => $owner->id,
        ]);
        $tag = Tag::factory()->create();

        $deal->tags()->attach($tag->id);

        $this->assertTrue($company->contacts->contains($contact));
        $this->assertTrue($contact->deals->contains($deal));
        $this->assertTrue($stage->deals->contains($deal));
        $this->assertTrue($deal->quotes->contains($quote));
        $this->assertTrue($quote->items->contains($item));
        $this->assertTrue($deal->tasks->contains($task));
        $this->assertTrue($contact->activities->contains($activity));
        $this->assertTrue($tag->deals->contains($deal));
    }

    public function test_query_scopes_filter_expected_records(): void
    {
        $owner = User::factory()->create();
        $stage = DealStage::factory()->create();

        Contact::factory()->create([
            'full_name' => 'Alice Example',
            'owner_id' => $owner->id,
        ]);
        Contact::factory()->create(['full_name' => 'Bob Example']);

        Deal::factory()->create(['stage_id' => $stage->id, 'status' => 'open']);
        Deal::factory()->won()->create(['stage_id' => $stage->id]);
        Deal::factory()->lost()->create();

        CrmTask::factory()->create(['due_at' => now()->addDay(), 'completed_at' => null]);
        CrmTask::factory()->create(['due_at' => now()->subDay(), 'completed_at' => null]);

        Quote::factory()->create([
            'contact_id' => null,
            'company_id' => null,
            'deal_id' => null,
            'status' => 'sent',
            'valid_until' => now()->addWeek(),
        ]);
        Quote::factory()->accepted()->create([
            'contact_id' => null,
            'company_id' => null,
            'deal_id' => null,
        ]);

        $this->assertSame(1, Contact::query()->search('Alice')->count());
        $this->assertSame(1, Contact::query()->ownedBy($owner->id)->count());
        $this->assertSame(1, Deal::query()->open()->count());
        $this->assertSame(1, Deal::query()->won()->count());
        $this->assertSame(1, Deal::query()->lost()->count());
        $this->assertSame(2, Deal::query()->forStage($stage->id)->count());
        $this->assertSame(1, CrmTask::query()->dueSoon()->count());
        $this->assertSame(1, CrmTask::query()->overdue()->count());
        $this->assertSame(1, Quote::query()->active()->count());
        $this->assertSame(1, Quote::query()->accepted()->count());
    }

    public function test_business_models_soft_delete(): void
    {
        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create();
        $quote = Quote::factory()->create();

        $contact->delete();
        $deal->delete();
        $quote->delete();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
        $this->assertSoftDeleted('deals', ['id' => $deal->id]);
        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);
    }
}
