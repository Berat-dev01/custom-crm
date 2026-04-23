<?php

namespace Sanalkopru\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Events\QuoteSent;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Services\Audit\CrmAuditLogger;

class SendQuote
{
    public function __construct(private readonly CrmAuditLogger $audit) {}

    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        $before = $quote->only(['status', 'sent_at']);

        $quote->forceFill([
            'status' => 'sent',
            'sent_at' => $quote->sent_at ?: now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        event(new QuoteSent($quote->refresh(), $user));
        $this->audit->record('crm.quote.sent', $quote, $user, $before, $quote->only(['status', 'sent_at']));

        return $quote;
    }
}
