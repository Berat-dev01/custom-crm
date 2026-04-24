<?php

namespace Sanalkopru\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Sanalkopru\Crm\Actions\Deals\MoveDealToStage;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Services\Audit\CrmAuditLogger;
use Sanalkopru\Crm\Services\Notifications\CrmBusinessNotifier;

class AcceptQuote
{
    public function __construct(
        private readonly MoveDealToStage $moveDeal,
        private readonly CrmAuditLogger $audit,
        private readonly CrmBusinessNotifier $notifications
    ) {}

    public function handle(Quote $quote, bool $markDealWon = false, ?Authenticatable $user = null): Quote
    {
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
                    $quote->deal->forceFill([
                        'status' => 'won',
                        'probability' => 100,
                        'closed_at' => $quote->deal->closed_at ?: now(),
                        'lost_reason' => null,
                        'updated_by' => $user?->getAuthIdentifier(),
                    ])->save();
                }
            }

            $quote = $quote->refresh();
            $this->audit->record('crm.quote.accepted', $quote, $user, $before, $quote->only(['status', 'accepted_at', 'rejected_at']));

            if ($statusChanged) {
                $this->notifications->quoteStatusChanged($quote->fresh(['owner', 'company', 'deal.owner']), 'accepted', $user);
            }

            return $quote;
        });
    }
}
