<?php

namespace Sanalkopru\Crm\Actions\Deals;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\QuoteItem;

class CreateDealQuote
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Deal $deal, array $payload, ?Authenticatable $user = null): Quote
    {
        return DB::transaction(function () use ($deal, $payload, $user): Quote {
            $quantity = (float) $payload['quantity'];
            $unitPrice = (float) $payload['unit_price'];
            $taxRate = (float) ($payload['tax_rate'] ?? config('crm.money.default_tax_rate', 20));
            $subtotal = round($quantity * $unitPrice, 2);
            $taxTotal = round($subtotal * ($taxRate / 100), 2);

            $quote = Quote::query()->create([
                'quote_number' => $this->nextQuoteNumber(),
                'contact_id' => $deal->contact_id,
                'company_id' => $deal->company_id,
                'deal_id' => $deal->id,
                'status' => 'draft',
                'currency' => $payload['currency'],
                'subtotal' => $subtotal,
                'discount_type' => null,
                'discount_value' => 0,
                'discount_total' => 0,
                'tax_rate' => $taxRate,
                'tax_total' => $taxTotal,
                'grand_total' => $subtotal + $taxTotal,
                'valid_until' => $payload['valid_until'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'terms' => $payload['terms'] ?? null,
                'owner_id' => $payload['owner_id'] ?? $deal->owner_id,
                'created_by' => $user?->getAuthIdentifier(),
            ]);

            QuoteItem::query()->create([
                'quote_id' => $quote->id,
                'name' => $payload['item_name'],
                'description' => $payload['item_description'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_type' => null,
                'discount_value' => 0,
                'tax_rate' => $taxRate,
                'line_total' => $subtotal + $taxTotal,
                'position' => 1,
                'created_by' => $user?->getAuthIdentifier(),
            ]);

            return $quote->refresh();
        });
    }

    private function nextQuoteNumber(): string
    {
        $prefix = (string) config('crm.quotes.number_prefix', 'CRM-');
        $padding = (int) config('crm.quotes.number_padding', 6);
        $next = ((int) Quote::query()->max('id')) + 1;

        do {
            $quoteNumber = $prefix.str_pad((string) $next, $padding, '0', STR_PAD_LEFT);
            $next++;
        } while (Quote::query()->where('quote_number', $quoteNumber)->exists());

        return $quoteNumber;
    }
}
