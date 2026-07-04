<?php

namespace App\Crm\Http\Resources\Api;

use App\Crm\Http\Resources\Api\Concerns\FormatsCrmApiResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    use FormatsCrmApiResource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'title' => $this->title,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ] : null),
            'lifecycle_stage' => $this->lifecycle_stage,
            'lifecycle_stage_label' => $this->labelFor($this->lifecycle_stage),
            'source' => $this->source,
            'source_label' => $this->labelFor($this->source),
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn () => $this->userSummary($this->owner)),
            'tags' => $this->tagSummaries($this->whenLoaded('tags')),
            'counts' => [
                'deals' => $this->whenCounted('deals'),
                'tasks' => $this->whenCounted('tasks'),
                'quotes' => $this->whenCounted('quotes'),
            ],
            'custom_fields' => $this->custom_fields,
            'last_contacted_at' => $this->last_contacted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
