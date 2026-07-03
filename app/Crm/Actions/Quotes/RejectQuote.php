<?php

namespace App\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\Quote;
use App\Crm\Services\Audit\CrmAuditLogger;
use App\Crm\Services\Notifications\CrmBusinessNotifier;

class RejectQuote
{
    public function __construct(
        private readonly CrmAuditLogger $audit,
        private readonly CrmBusinessNotifier $notifications
    ) {}

    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        if ($quote->status === Quote::STATUS_REJECTED) {
            return $quote;
        }

        $quote->assertCanTransitionTo(Quote::STATUS_REJECTED);

        $before = $quote->only(['status', 'rejected_at']);
        $statusChanged = ($before['status'] ?? null) !== 'rejected';

        $quote->forceFill([
            'status' => 'rejected',
            'rejected_at' => $quote->rejected_at ?: now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        $quote = $quote->refresh();
        $this->audit->record('crm.quote.rejected', $quote, $user, $before, $quote->only(['status', 'rejected_at']));

        if ($statusChanged) {
            $this->notifications->quoteStatusChanged($quote->fresh(['owner', 'company', 'deal.owner']), 'rejected', $user);
        }

        return $quote;
    }
}
