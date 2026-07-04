<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Contact;
use App\Crm\Models\Quote;
use App\Crm\Models\QuoteItem;
use Tests\TestCase;

class CrmPublicQuoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
    }

    private function sentQuote(): Quote
    {
        $contact = Contact::factory()->create(['full_name' => 'Public Customer']);
        $quote = Quote::factory()->create([
            'status' => 'sent',
            'contact_id' => $contact->id,
            'deal_id' => null,
            'grand_total' => 1200,
        ]);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'name' => 'Lisans paketi']);
        $quote->ensurePublicToken();

        return $quote->refresh();
    }

    public function test_public_quote_page_renders_without_login(): void
    {
        $quote = $this->sentQuote();

        $this->get(route('crm.public.quote.show', $quote->public_token))
            ->assertOk()
            ->assertSee($quote->quote_number)
            ->assertSee('Lisans paketi')
            ->assertSee(trans('crm::public.quote.accept'));
    }

    public function test_invalid_token_returns_404(): void
    {
        $this->get('/quote/'.str_repeat('x', 64))->assertNotFound();
        $this->get('/quote/short')->assertNotFound();
    }

    public function test_customer_can_accept_quote(): void
    {
        $quote = $this->sentQuote();

        $this->post(route('crm.public.quote.accept', $quote->public_token))
            ->assertRedirect(route('crm.public.quote.show', $quote->public_token));

        $this->assertSame('accepted', $quote->refresh()->status);
        $this->assertNotNull($quote->accepted_at);
    }

    public function test_customer_can_reject_quote_with_reason(): void
    {
        $quote = $this->sentQuote();

        $this->post(route('crm.public.quote.reject', $quote->public_token), [
            'reason' => 'Bütçemizi aşıyor.',
        ])->assertRedirect(route('crm.public.quote.show', $quote->public_token));

        $this->assertSame('rejected', $quote->refresh()->status);
        $this->assertDatabaseHas('activities', [
            'activityable_type' => 'quote',
            'activityable_id' => $quote->id,
            'body' => 'Bütçemizi aşıyor.',
        ]);
    }

    public function test_accept_is_ignored_when_quote_is_not_sent(): void
    {
        $quote = $this->sentQuote();
        $quote->forceFill(['status' => 'rejected'])->save();

        $this->post(route('crm.public.quote.accept', $quote->public_token))
            ->assertRedirect();

        $this->assertSame('rejected', $quote->refresh()->status);
    }

    public function test_public_pdf_download_works(): void
    {
        $quote = $this->sentQuote();

        $this->get(route('crm.public.quote.download', $quote->public_token))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_sending_quote_generates_public_token(): void
    {
        $admin = User::factory()->create()->assignRole('crm_owner');
        $quote = Quote::factory()->create(['status' => 'draft', 'deal_id' => null, 'public_token' => null]);

        $this->actingAs($admin, 'admin')
            ->patch(route('crm.quotes.send', $quote))
            ->assertRedirect();

        $this->assertNotNull($quote->refresh()->public_token);
    }
}
