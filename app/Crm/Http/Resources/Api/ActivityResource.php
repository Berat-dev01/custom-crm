<?php

namespace App\Crm\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'type' => $this->type,
            'subject' => $this->subject,
            'body' => $this->body,
            'is_system' => (bool) $this->is_system,
            'activityable_type' => $this->activityable_type ? class_basename($this->activityable_type) : null,
            'activityable_id' => $this->activityable_id,
            'user_id' => $this->user_id,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
