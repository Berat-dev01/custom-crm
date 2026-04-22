<?php

namespace Sanalkopru\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\QuoteItem;
use Sanalkopru\Crm\Services\Quotes\QuoteCalculator;
use Sanalkopru\Crm\Services\Quotes\QuoteNumberGenerator;

class UpsertQuote
{
    public function __construct(
        private readonly QuoteCalculator $calculator,
        private readonly QuoteNumberGenerator $numbers
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Quote $quote, array $payload, ?Authenticatable $user = null): Quote
    {
        return DB::transaction(function () use ($quote, $payload, $user): Quote {
            $calculation = $this->calculator->calculate($payload);
            $relationshipPayload = $this->relationshipPayload($payload);

            $quote->fill(array_merge($relationshipPayload, [
                'quote_number' => $quote->quote_number ?: $this->numbers->next(),
                'status' => $payload['status'] ?? $quote->status ?: 'draft',
                'currency' => $payload['currency'],
                'discount_type' => $payload['discount_type'] ?? null,
                'discount_value' => $calculation['quote']['discount_value'],
                'discount_total' => $calculation['quote']['discount_total'],
                'tax_rate' => $calculation['quote']['tax_rate'],
                'tax_total' => $calculation['quote']['tax_total'],
                'subtotal' => $calculation['quote']['subtotal'],
                'grand_total' => $calculation['quote']['grand_total'],
                'valid_until' => $payload['valid_until'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'terms' => $payload['terms'] ?? null,
                'owner_id' => $payload['owner_id'] ?? $quote->owner_id ?? $user?->getAuthIdentifier(),
                'updated_by' => $user?->getAuthIdentifier(),
            ]));

            if (! $quote->exists) {
                $quote->created_by = $user?->getAuthIdentifier();
            }

            $quote->save();
            $this->syncItems($quote, $calculation['items'], $user);
            $quote->tags()->sync($payload['tag_ids'] ?? []);

            return $quote->refresh()->load(['items', 'tags', 'company', 'contact', 'deal', 'owner']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function relationshipPayload(array $payload): array
    {
        $deal = ! empty($payload['deal_id'])
            ? Deal::query()->find($payload['deal_id'])
            : null;

        return [
            'contact_id' => $payload['contact_id'] ?? $deal?->contact_id,
            'company_id' => $payload['company_id'] ?? $deal?->company_id,
            'deal_id' => $payload['deal_id'] ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function syncItems(Quote $quote, array $items, ?Authenticatable $user): void
    {
        $quote->items()->delete();

        foreach ($items as $item) {
            QuoteItem::query()->create(array_merge($item, [
                'quote_id' => $quote->id,
                'created_by' => $user?->getAuthIdentifier(),
                'updated_by' => $user?->getAuthIdentifier(),
            ]));
        }
    }
}
