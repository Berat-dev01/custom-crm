<?php

namespace Sanalkopru\Crm\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Sanalkopru\Crm\Http\Resources\Api\Concerns\FormatsCrmApiResource;

class QuoteResource extends JsonResource
{
    use FormatsCrmApiResource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'quote_number' => $this->quote_number,
            'status' => $this->status,
            'status_label' => $this->labelFor($this->status),
            'contact_id' => $this->contact_id,
            'company_id' => $this->company_id,
            'deal_id' => $this->deal_id,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ] : null),
            'contact' => $this->whenLoaded('contact', fn () => $this->contact ? [
                'id' => $this->contact->id,
                'full_name' => $this->contact->full_name,
            ] : null),
            'deal' => $this->whenLoaded('deal', fn () => $this->deal ? [
                'id' => $this->deal->id,
                'title' => $this->deal->title,
            ] : null),
            'currency' => $this->currency,
            'discount_type' => $this->discount_type,
            'discount_type_label' => $this->labelFor($this->discount_type),
            'discount_value' => (float) $this->discount_value,
            'discount_total' => (float) $this->discount_total,
            'tax_rate' => (float) $this->tax_rate,
            'tax_total' => (float) $this->tax_total,
            'subtotal' => (float) $this->subtotal,
            'grand_total' => (float) $this->grand_total,
            'valid_until' => $this->valid_until?->toDateString(),
            'notes' => $this->notes,
            'terms' => $this->terms,
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn () => $this->userSummary($this->owner)),
            'tags' => $this->tagSummaries($this->whenLoaded('tags')),
            'items' => QuoteItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'sent_at' => $this->sent_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
