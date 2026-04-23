<?php

namespace Sanalkopru\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Services\Audit\CrmAuditLogger;

class RejectQuote
{
    public function __construct(private readonly CrmAuditLogger $audit) {}

    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        $before = $quote->only(['status', 'rejected_at']);

        $quote->forceFill([
            'status' => 'rejected',
            'rejected_at' => $quote->rejected_at ?: now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        $quote = $quote->refresh();
        $this->audit->record('crm.quote.rejected', $quote, $user, $before, $quote->only(['status', 'rejected_at']));

        return $quote;
    }
}
