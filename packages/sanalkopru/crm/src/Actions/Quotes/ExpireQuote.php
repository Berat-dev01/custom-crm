<?php

namespace Sanalkopru\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Quote;

class ExpireQuote
{
    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        $quote->forceFill([
            'status' => 'expired',
            'valid_until' => now()->toDateString(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        return $quote->refresh();
    }
}
