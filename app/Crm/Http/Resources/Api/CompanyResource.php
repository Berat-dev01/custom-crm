<?php

namespace App\Crm\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Crm\Http\Resources\Api\Concerns\FormatsCrmApiResource;

class CompanyResource extends JsonResource
{
    use FormatsCrmApiResource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'tax_number' => $this->tax_number,
            'tax_office' => $this->tax_office,
            'sector' => $this->sector,
            'address' => [
                'line_1' => $this->address_line_1,
                'line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn () => $this->userSummary($this->owner)),
            'tags' => $this->tagSummaries($this->whenLoaded('tags')),
            'counts' => [
                'contacts' => $this->whenCounted('contacts'),
                'deals' => $this->whenCounted('deals'),
                'quotes' => $this->whenCounted('quotes'),
            ],
            'custom_fields' => $this->custom_fields,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
