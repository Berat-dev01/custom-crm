<?php

namespace Sanalkopru\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Sanalkopru\Crm\Actions\Deals\MoveDealToStage;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;

class AcceptQuote
{
    public function __construct(private readonly MoveDealToStage $moveDeal) {}

    public function handle(Quote $quote, bool $markDealWon = false, ?Authenticatable $user = null): Quote
    {
        return DB::transaction(function () use ($quote, $markDealWon, $user): Quote {
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

            return $quote->refresh();
        });
    }
}
