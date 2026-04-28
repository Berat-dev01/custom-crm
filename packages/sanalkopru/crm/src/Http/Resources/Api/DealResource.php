<?php

namespace Sanalkopru\Crm\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Sanalkopru\Crm\Http\Resources\Api\Concerns\FormatsCrmApiResource;

class DealResource extends JsonResource
{
    use FormatsCrmApiResource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'title' => $this->title,
            'contact_id' => $this->contact_id,
            'company_id' => $this->company_id,
            'stage_id' => $this->stage_id,
            'stage' => $this->whenLoaded('stage', fn () => $this->stage ? [
                'id' => $this->stage->id,
                'name' => $this->stage->name,
                'probability' => $this->stage->probability,
                'is_won' => $this->stage->is_won,
                'is_lost' => $this->stage->is_lost,
            ] : null),
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ] : null),
            'contact' => $this->whenLoaded('contact', fn () => $this->contact ? [
                'id' => $this->contact->id,
                'full_name' => $this->contact->full_name,
            ] : null),
            'value' => (float) $this->value,
            'currency' => $this->currency,
            'probability' => $this->probability,
            'position' => $this->position,
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'status' => $this->status,
            'status_label' => $this->labelFor($this->status),
            'lost_reason' => $this->lost_reason,
            'closed_at' => $this->closed_at?->toISOString(),
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn () => $this->userSummary($this->owner)),
            'tags' => $this->tagSummaries($this->whenLoaded('tags')),
            'counts' => [
                'open_tasks' => $this->whenCounted('open_tasks'),
            ],
            'custom_fields' => $this->custom_fields,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
