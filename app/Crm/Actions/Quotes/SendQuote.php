<?php

namespace App\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Events\QuoteSent;
use App\Crm\Models\Quote;
use App\Crm\Services\Audit\CrmAuditLogger;
use App\Crm\Services\Notifications\CrmBusinessNotifier;

class SendQuote
{
    public function __construct(
        private readonly CrmAuditLogger $audit,
        private readonly CrmBusinessNotifier $notifications
    ) {}

    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        $before = $quote->only(['status', 'sent_at']);
        $statusChanged = ($before['status'] ?? null) !== 'sent';

        $quote->forceFill([
            'status' => 'sent',
            'sent_at' => $quote->sent_at ?: now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        event(new QuoteSent($quote->refresh(), $user));
        $this->audit->record('crm.quote.sent', $quote, $user, $before, $quote->only(['status', 'sent_at']));

        if ($statusChanged) {
            $this->notifications->quoteStatusChanged($quote->fresh(['owner', 'company', 'deal.owner']), 'sent', $user);
        }

        return $quote;
    }
}
