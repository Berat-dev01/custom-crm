<?php

namespace Sanalkopru\Crm\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Sanalkopru\Crm\Http\Resources\Api\Concerns\FormatsCrmApiResource;

class TaskResource extends JsonResource
{
    use FormatsCrmApiResource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'priority_label' => $this->labelFor($this->priority),
            'status' => $this->status,
            'status_label' => $this->labelFor($this->status),
            'assigned_to' => $this->assigned_to,
            'assignee' => $this->whenLoaded('assignee', fn () => $this->userSummary($this->assignee)),
            'taskable' => $this->whenLoaded('taskable', fn () => $this->taskable ? [
                'type' => class_basename($this->taskable_type),
                'type_key' => $this->relatedRecordTypeKey($this->taskable_type),
                'type_label' => $this->relatedRecordTypeLabel($this->taskable_type),
                'id' => $this->taskable_id,
                'label' => $this->taskable->title ?? $this->taskable->full_name ?? $this->taskable->name ?? $this->taskable->quote_number ?? null,
            ] : null),
            'due_at' => $this->due_at?->toISOString(),
            'reminder_at' => $this->reminder_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
