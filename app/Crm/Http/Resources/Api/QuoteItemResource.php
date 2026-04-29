<?php

namespace App\Crm\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Crm\Http\Resources\Api\Concerns\FormatsCrmApiResource;

class QuoteItemResource extends JsonResource
{
    use FormatsCrmApiResource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount_type' => $this->discount_type,
            'discount_type_label' => $this->labelFor($this->discount_type),
            'discount_value' => (float) $this->discount_value,
            'discount_total' => (float) $this->discount_total,
            'tax_rate' => (float) $this->tax_rate,
            'tax_total' => (float) $this->tax_total,
            'line_total' => (float) $this->line_total,
            'position' => $this->position,
        ];
    }
}
