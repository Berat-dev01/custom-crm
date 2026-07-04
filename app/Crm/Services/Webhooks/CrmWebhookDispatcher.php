<?php

namespace App\Crm\Services\Webhooks;

use App\Crm\Jobs\SendCrmWebhook;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\CrmWebhook;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Models\Task;
use Illuminate\Database\Eloquent\Model;

class CrmWebhookDispatcher
{
    /**
     * Queue the event to every active webhook subscribed to it.
     */
    public function dispatch(string $event, Model $subject): void
    {
        if (! config('crm.features.webhooks')) {
            return;
        }

        $webhooks = CrmWebhook::query()
            ->active()
            ->subscribedTo($event)
            ->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        $payload = [
            'event' => $event,
            'triggered_at' => now()->toIso8601String(),
            'data' => $this->payloadFor($subject),
        ];

        foreach ($webhooks as $webhook) {
            $delivery = $webhook->deliveries()->create([
                'event' => $event,
                'payload' => $payload,
                'status' => 'pending',
            ]);

            SendCrmWebhook::dispatch($delivery);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(Model $subject): array
    {
        return match (true) {
            $subject instanceof Deal => [
                'type' => 'deal',
                'id' => $subject->id,
                'title' => $subject->title,
                'status' => $subject->status,
                'value' => (float) $subject->value,
                'stage_id' => $subject->stage_id,
                'company_id' => $subject->company_id,
                'contact_id' => $subject->contact_id,
                'owner_id' => $subject->owner_id,
                'closed_at' => $subject->closed_at?->toIso8601String(),
                'lost_reason' => $subject->lost_reason,
            ],
            $subject instanceof Quote => [
                'type' => 'quote',
                'id' => $subject->id,
                'quote_number' => $subject->quote_number,
                'status' => $subject->status,
                'currency' => $subject->currency,
                'grand_total' => (float) $subject->grand_total,
                'contact_id' => $subject->contact_id,
                'company_id' => $subject->company_id,
                'deal_id' => $subject->deal_id,
                'valid_until' => $subject->valid_until?->toDateString(),
            ],
            $subject instanceof Contact => [
                'type' => 'contact',
                'id' => $subject->id,
                'full_name' => $subject->full_name,
                'email' => $subject->email,
                'phone' => $subject->phone,
                'company_id' => $subject->company_id,
                'lifecycle_stage' => $subject->lifecycle_stage,
            ],
            $subject instanceof Company => [
                'type' => 'company',
                'id' => $subject->id,
                'name' => $subject->name,
                'email' => $subject->email,
                'phone' => $subject->phone,
                'sector' => $subject->sector,
                'city' => $subject->city,
            ],
            $subject instanceof Task => [
                'type' => 'task',
                'id' => $subject->id,
                'title' => $subject->title,
                'status' => $subject->status,
                'priority' => $subject->priority,
                'assigned_to' => $subject->assigned_to,
                'due_at' => $subject->due_at?->toIso8601String(),
            ],
            default => [
                'type' => strtolower(class_basename($subject)),
                'id' => $subject->getKey(),
            ],
        };
    }
}
