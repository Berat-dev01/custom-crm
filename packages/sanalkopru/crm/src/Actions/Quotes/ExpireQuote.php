<?php

namespace Sanalkopru\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Services\Notifications\CrmBusinessNotifier;

class ExpireQuote
{
    public function __construct(private readonly CrmBusinessNotifier $notifications) {}

    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        $statusChanged = $quote->status !== 'expired';

        $quote->forceFill([
            'status' => 'expired',
            'valid_until' => now()->toDateString(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        $quote = $quote->refresh();

        if ($statusChanged) {
            $this->notifications->quoteStatusChanged($quote->fresh(['owner', 'company', 'deal.owner']), 'expired', $user);
        }

        return $quote;
    }
}
