<?php

namespace Sanalkopru\Crm\Services\Configuration;

class MoneySettings
{
    public function defaultCurrency(): string
    {
        return (string) config('crm.money.default_currency', 'TRY');
    }

    /**
     * @return list<string>
     */
    public function supportedCurrencies(): array
    {
        return array_values((array) config('crm.money.supported_currencies', ['TRY']));
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array($currency, $this->supportedCurrencies(), true);
    }

    public function defaultTaxRate(): float
    {
        return (float) config('crm.money.default_tax_rate', 20);
    }

    public function quoteNumberPrefix(): string
    {
        return (string) config('crm.quotes.number_prefix', 'CRM-');
    }

    public function quoteNumberPadding(): int
    {
        return (int) config('crm.quotes.number_padding', 6);
    }
}
