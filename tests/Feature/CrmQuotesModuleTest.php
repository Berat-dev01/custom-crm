<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\QuoteItem;
use Sanalkopru\Crm\Models\Tag;
use Tests\TestCase;

class CrmQuotesModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->admin = User::factory()->create()->assignRole('crm_owner');
    }

    public function test_quote_crud_recalculates_totals_from_backend_items(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Quote']);
        $contact = Contact::factory()->create(['company_id' => $company->id, 'full_name' => 'Ada Buyer']);
        $tag = Tag::factory()->create(['name' => 'Priority Quote', 'slug' => 'priority-quote']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.quotes.create'))
            ->assertOk()
            ->assertSee('Line Items')
            ->assertSee('Add Line');

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.quotes.store'), [
                'company_id' => $company->id,
                'contact_id' => $contact->id,
                'status' => 'draft',
                'currency' => 'TRY',
                'discount_type' => 'fixed',
                'discount_value' => 100,
                'valid_until' => '2026-05-30',
                'notes' => 'Backend controls the totals.',
                'terms' => 'Net 14.',
                'tag_ids' => [$tag->id],
                'subtotal' => 1,
                'grand_total' => 1,
                'items' => [
                    [
                        'name' => 'Implementation',
                        'quantity' => 2,
                        'unit_price' => 1000,
                        'discount_type' => 'percentage',
                        'discount_value' => 10,
                        'tax_rate' => 20,
                        'position' => 2,
                    ],
                    [
                        'name' => 'Support',
                        'quantity' => 1,
                        'unit_price' => 500,
                        'discount_type' => 'fixed',
                        'discount_value' => 50,
                        'tax_rate' => 10,
                        'position' => 1,
                    ],
                ],
            ])
            ->assertRedirect();

        $quote = Quote::query()->firstOrFail();
        $this->assertSame('2500.00', $quote->subtotal);
        $this->assertSame('350.00', $quote->discount_total);
        $this->assertSame('2537.00', $quote->grand_total);
        $this->assertSame(2, $quote->items()->count());
        $this->assertTrue($quote->tags()->whereKey($tag->id)->exists());

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.quotes.show', $quote))
            ->assertOk()
            ->assertSee('Backend controls the totals.')
            ->assertSee('2,537.00');

        $this->actingAs($this->admin, 'admin')
            ->put(route('crm.quotes.update', $quote), [
                'company_id' => $company->id,
                'contact_id' => $contact->id,
                'status' => 'draft',
                'currency' => 'TRY',
                'discount_type' => null,
                'discount_value' => 0,
                'items' => [
                    [
                        'name' => 'Updated line',
                        'quantity' => 1,
                        'unit_price' => 1200,
                        'tax_rate' => 20,
                        'position' => 1,
                    ],
                ],
            ])
            ->assertRedirect(route('crm.quotes.show', $quote));

        $quote->refresh();
        $this->assertSame('1440.00', $quote->grand_total);
        $this->assertSame(1, QuoteItem::query()->where('quote_id', $quote->id)->count());
    }

    public function test_quote_index_filters_by_status_owner_tag_and_search(): void
    {
        $tag = Tag::factory()->create(['name' => 'Enterprise', 'slug' => 'enterprise']);
        $matching = Quote::factory()->create([
            'quote_number' => 'CRM-MATCH-001',
            'status' => 'sent',
            'owner_id' => $this->admin->id,
        ]);
        $matching->tags()->attach($tag->id);
        Quote::factory()->create(['quote_number' => 'CRM-OTHER-001', 'status' => 'draft']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.quotes.index', [
                'search' => 'MATCH',
                'status' => 'sent',
                'owner_id' => $this->admin->id,
                'tag_id' => $tag->id,
            ]))
            ->assertOk()
            ->assertSee('CRM-MATCH-001')
            ->assertDontSee('CRM-OTHER-001');
    }

    public function test_quote_status_actions_and_duplicate_work(): void
    {
        $open = DealStage::factory()->create(['name' => 'Open', 'slug' => 'open', 'position' => 1, 'is_won' => false, 'is_lost' => false]);
        $won = DealStage::factory()->won()->create(['position' => 2]);
        $deal = Deal::factory()->create(['stage_id' => $open->id, 'status' => 'open']);
        $quote = Quote::factory()->create(['deal_id' => $deal->id, 'status' => 'draft']);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'position' => 1]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.quotes.send', $quote))
            ->assertRedirect(route('crm.quotes.show', $quote));

        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->assertNotNull($quote->sent_at);
        $this->assertDatabaseHas('activities', [
            'activityable_type' => $deal::class,
            'activityable_id' => $deal->id,
            'type' => 'quote_sent',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.quotes.accept', $quote), ['mark_deal_won' => 1])
            ->assertRedirect(route('crm.quotes.show', $quote));

        $quote->refresh();
        $deal->refresh();
        $this->assertSame('accepted', $quote->status);
        $this->assertSame('won', $deal->status);
        $this->assertSame($won->id, $deal->stage_id);

        $this->actingAs($this->admin, 'admin')
            ->post(route('crm.quotes.duplicate', $quote))
            ->assertRedirect();

        $copy = Quote::query()->whereKeyNot($quote->id)->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertNotSame($quote->quote_number, $copy->quote_number);
        $this->assertSame(1, $copy->items()->count());

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.quotes.reject', $copy))
            ->assertRedirect(route('crm.quotes.show', $copy));
        $this->assertSame('rejected', $copy->refresh()->status);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('crm.quotes.expire', $copy))
            ->assertRedirect(route('crm.quotes.show', $copy));
        $this->assertSame('expired', $copy->refresh()->status);
    }

    public function test_quote_pdf_preview_and_download_are_available(): void
    {
        $quote = Quote::factory()->create(['quote_number' => 'CRM-PDF-001']);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'name' => 'PDF Service']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('crm.quotes.preview', $quote))
            ->assertOk()
            ->assertSee('CRM-PDF-001')
            ->assertSee('PDF Service');

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('crm.quotes.download', $quote))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_quote_actions_are_policy_protected(): void
    {
        $viewer = User::factory()->create()->assignRole('crm_viewer');
        $quote = Quote::factory()->create();

        $this->actingAs($viewer, 'admin')
            ->post(route('crm.quotes.store'), [
                'currency' => 'TRY',
                'items' => [['name' => 'Nope', 'quantity' => 1, 'unit_price' => 1]],
            ])
            ->assertForbidden();

        $this->actingAs($viewer, 'admin')
            ->patch(route('crm.quotes.send', $quote))
            ->assertForbidden();

        $this->actingAs($viewer, 'admin')
            ->get(route('crm.quotes.download', $quote))
            ->assertForbidden();
    }
}
