<?php

namespace Sanalkopru\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Events\QuoteSent;
use Sanalkopru\Crm\Models\Quote;

class SendQuote
{
    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        $quote->forceFill([
            'status' => 'sent',
            'sent_at' => $quote->sent_at ?: now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        event(new QuoteSent($quote->refresh(), $user));

        return $quote;
    }
}
