<?php

namespace Sanalkopru\Crm\Services\Quotes;

use Sanalkopru\Crm\Models\Quote;

class QuoteNumberGenerator
{
    public function next(): string
    {
        $prefix = (string) config('crm.quotes.number_prefix', 'CRM-');
        $padding = (int) config('crm.quotes.number_padding', 6);
        $next = ((int) Quote::query()->withTrashed()->max('id')) + 1;

        do {
            $quoteNumber = $prefix.str_pad((string) $next, $padding, '0', STR_PAD_LEFT);
            $next++;
        } while (Quote::query()->withTrashed()->where('quote_number', $quoteNumber)->exists());

        return $quoteNumber;
    }
}
