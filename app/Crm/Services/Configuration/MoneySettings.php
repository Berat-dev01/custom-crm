<?php

namespace App\Crm\Services\Configuration;

use App\Crm\Services\Settings\CrmSettingsManager;

class MoneySettings
{
    public function __construct(private readonly CrmSettingsManager $settings) {}

    public function defaultCurrency(): string
    {
        return (string) $this->settings->get('default_currency', config('crm.money.default_currency', 'TRY'));
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
        return (float) $this->settings->get('default_tax_rate', config('crm.money.default_tax_rate', 20));
    }

    public function quoteNumberPrefix(): string
    {
        return (string) $this->settings->get('quote_prefix', config('crm.quotes.number_prefix', 'CRM-'));
    }

    public function quoteNumberPadding(): int
    {
        return (int) config('crm.quotes.number_padding', 6);
    }

    public function quoteTerms(): ?string
    {
        $terms = $this->settings->get('quote_terms', config('crm.quotes.default_terms'));

        return is_string($terms) && $terms !== '' ? $terms : null;
    }
}
