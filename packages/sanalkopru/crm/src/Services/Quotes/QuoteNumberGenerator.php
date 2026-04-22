<?php

namespace Sanalkopru\Crm\Services\Quotes;

use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Services\Configuration\MoneySettings;

class QuoteNumberGenerator
{
    public function __construct(private readonly MoneySettings $money) {}

    public function next(): string
    {
        $prefix = $this->money->quoteNumberPrefix();
        $padding = $this->money->quoteNumberPadding();
        $next = ((int) Quote::query()->withTrashed()->max('id')) + 1;

        do {
            $quoteNumber = $prefix.str_pad((string) $next, $padding, '0', STR_PAD_LEFT);
            $next++;
        } while (Quote::query()->withTrashed()->where('quote_number', $quoteNumber)->exists());

        return $quoteNumber;
    }
}
