<?php

namespace App\Crm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Crm\Models\CrmWebhookDelivery;

class SendCrmWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function __construct(public readonly CrmWebhookDelivery $delivery) {}

    public function handle(): void
    {
        $delivery = $this->delivery->fresh('webhook');

        if (! $delivery || ! $delivery->webhook || ! $delivery->webhook->is_active) {
            return;
        }

        $body = json_encode($delivery->payload, JSON_UNESCAPED_UNICODE);

        $delivery->forceFill([
            'attempts' => $delivery->attempts + 1,
            'last_attempt_at' => now(),
        ])->save();

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-CRM-Event' => $delivery->event,
                    'X-CRM-Delivery' => $delivery->public_id,
                    'X-CRM-Signature' => $delivery->webhook->signature($body),
                ])
                ->withBody($body, 'application/json')
                ->post($delivery->webhook->url);

            $delivery->forceFill([
                'status' => $response->successful() ? 'success' : 'failed',
                'response_status' => $response->status(),
            ])->save();

            if (! $response->successful()) {
                $this->release($this->backoff()[min($this->attempts() - 1, 2)]);
            }
        } catch (\Throwable $exception) {
            $delivery->forceFill(['status' => 'failed'])->save();

            Log::warning('CRM webhook delivery failed', [
                'delivery_id' => $delivery->id,
                'webhook_id' => $delivery->webhook_id,
                'url' => $delivery->webhook->url,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
