<?php

namespace App\Crm\Actions\Deals;

use App\Crm\Actions\Quotes\UpsertQuote;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Services\Configuration\MoneySettings;
use Illuminate\Contracts\Auth\Authenticatable;

class CreateDealQuote
{
    public function __construct(
        private readonly UpsertQuote $upsertQuote,
        private readonly MoneySettings $money
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Deal $deal, array $payload, ?Authenticatable $user = null): Quote
    {
        return $this->upsertQuote->handle(new Quote, [
            'contact_id' => $deal->contact_id,
            'company_id' => $deal->company_id,
            'deal_id' => $deal->id,
            'status' => 'draft',
            'currency' => $payload['currency'],
            'discount_type' => null,
            'discount_value' => 0,
            'valid_until' => $payload['valid_until'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'terms' => $payload['terms'] ?? $this->money->quoteTerms(),
            'owner_id' => $payload['owner_id'] ?? $deal->owner_id,
            'items' => [[
                'name' => $payload['item_name'],
                'description' => $payload['item_description'] ?? null,
                'quantity' => $payload['quantity'],
                'unit_price' => $payload['unit_price'],
                'discount_type' => null,
                'discount_value' => 0,
                'tax_rate' => $payload['tax_rate'] ?? $this->money->defaultTaxRate(),
                'position' => 1,
            ]],
        ], $user);
    }
}
