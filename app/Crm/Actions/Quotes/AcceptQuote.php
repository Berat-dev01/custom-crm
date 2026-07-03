<?php

namespace App\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use App\Crm\Actions\Deals\MoveDealToStage;
use App\Crm\Models\DealStage;
use App\Crm\Models\Quote;
use App\Crm\Services\Audit\CrmAuditLogger;
use App\Crm\Services\Notifications\CrmBusinessNotifier;
use App\Crm\Services\Webhooks\CrmWebhookDispatcher;

class AcceptQuote
{
    public function __construct(
        private readonly MoveDealToStage $moveDeal,
        private readonly CrmAuditLogger $audit,
        private readonly CrmBusinessNotifier $notifications,
        private readonly CrmWebhookDispatcher $webhooks
    ) {}

    public function handle(Quote $quote, bool $markDealWon = false, ?Authenticatable $user = null): Quote
    {
        if ($quote->status === Quote::STATUS_ACCEPTED) {
            return $quote;
        }

        $quote->assertCanTransitionTo(Quote::STATUS_ACCEPTED);

        return DB::transaction(function () use ($quote, $markDealWon, $user): Quote {
            $before = $quote->only(['status', 'accepted_at', 'rejected_at']);
            $statusChanged = ($before['status'] ?? null) !== 'accepted';

            $quote->forceFill([
                'status' => 'accepted',
                'accepted_at' => $quote->accepted_at ?: now(),
                'rejected_at' => null,
                'updated_by' => $user?->getAuthIdentifier(),
            ])->save();

            if ($markDealWon && $quote->deal) {
                $wonStage = DealStage::query()->where('is_won', true)->first();

                if ($wonStage) {
                    $this->moveDeal->handle($quote->deal, $wonStage, null, null, $user);
                } else {
                    $wasWon = $quote->deal->status === 'won';

                    $quote->deal->forceFill([
                        'status' => 'won',
                        'probability' => 100,
                        'closed_at' => $quote->deal->closed_at ?: now(),
                        'lost_reason' => null,
                        'updated_by' => $user?->getAuthIdentifier(),
                    ])->save();

                    if (! $wasWon) {
                        $this->notifications->dealClosed($quote->deal->refresh(), 'won', $user);
                        $this->webhooks->dispatch('deal.won', $quote->deal);
                    }
                }
            }

            $quote = $quote->refresh();
            $this->audit->record('crm.quote.accepted', $quote, $user, $before, $quote->only(['status', 'accepted_at', 'rejected_at']));

            if ($statusChanged) {
                $this->notifications->quoteStatusChanged($quote->fresh(['owner', 'company', 'deal.owner']), 'accepted', $user);
                $this->webhooks->dispatch('quote.accepted', $quote);
            }

            return $quote;
        });
    }
}
