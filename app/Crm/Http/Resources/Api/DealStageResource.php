<?php

namespace App\Crm\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealStageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'position' => $this->position,
            'probability' => $this->probability,
            'is_won' => (bool) $this->is_won,
            'is_lost' => (bool) $this->is_lost,
            'color' => $this->color,
        ];
    }
}
