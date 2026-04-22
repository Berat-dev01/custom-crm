<?php

namespace Sanalkopru\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Quote;

class RejectQuote
{
    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        $quote->forceFill([
            'status' => 'rejected',
            'rejected_at' => $quote->rejected_at ?: now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        return $quote->refresh();
    }
}
