<?php

namespace App\Crm\Services\Quotes;

use App\Crm\Services\Configuration\MoneySettings;

class QuoteCalculator
{
    public function __construct(private readonly ?MoneySettings $money = null) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{quote: array<string, string>, items: list<array<string, mixed>>}
     */
    public function calculate(array $payload): array
    {
        $rawItems = array_values($payload['items'] ?? []);
        $subtotalCents = 0;
        $lineDiscountCents = 0;
        $baseAfterItemDiscountCents = 0;
        $items = [];

        foreach ($rawItems as $index => $item) {
            $quantityUnits = $this->decimalToMinorUnits($item['quantity'] ?? 1, 3);
            $unitPriceCents = $this->decimalToMinorUnits($item['unit_price'] ?? 0);
            $baseCents = $this->roundedDivide($quantityUnits * $unitPriceCents, 1000);
            $itemDiscountCents = $this->discountCents(
                $baseCents,
                $item['discount_type'] ?? null,
                $item['discount_value'] ?? 0
            );
            $taxableBeforeQuoteDiscountCents = max(0, $baseCents - $itemDiscountCents);
            $taxRateHundredths = $this->decimalToMinorUnits($item['tax_rate'] ?? $this->defaultTaxRate());
            $lineTaxCents = $this->percentageOf($taxableBeforeQuoteDiscountCents, $taxRateHundredths);

            $subtotalCents += $baseCents;
            $lineDiscountCents += $itemDiscountCents;
            $baseAfterItemDiscountCents += $taxableBeforeQuoteDiscountCents;

            $items[] = [
                'name' => $item['name'] ?? '',
                'description' => $item['description'] ?? null,
                'quantity' => $this->minorUnitsToDecimal($quantityUnits, 3),
                'unit_price' => $this->minorUnitsToDecimal($unitPriceCents),
                'discount_type' => $item['discount_type'] ?? null,
                'discount_value' => $this->normalDiscountValue($item['discount_type'] ?? null, $item['discount_value'] ?? 0),
                'tax_rate' => $this->minorUnitsToDecimal($taxRateHundredths),
                'line_total' => $this->minorUnitsToDecimal($taxableBeforeQuoteDiscountCents + $lineTaxCents),
                'position' => (int) ($item['position'] ?? ($index + 1)),
                'taxable_before_quote_discount_cents' => $taxableBeforeQuoteDiscountCents,
                'tax_rate_hundredths' => $taxRateHundredths,
            ];
        }

        $quoteDiscountCents = $this->discountCents(
            $baseAfterItemDiscountCents,
            $payload['discount_type'] ?? null,
            $payload['discount_value'] ?? 0
        );
        $taxTotalCents = $this->taxTotalAfterQuoteDiscount($items, $quoteDiscountCents, $baseAfterItemDiscountCents);
        $discountTotalCents = $lineDiscountCents + $quoteDiscountCents;
        $grandTotalCents = max(0, $subtotalCents - $discountTotalCents) + $taxTotalCents;
        $quoteTaxRate = $payload['tax_rate'] ?? ($items[0]['tax_rate'] ?? $this->defaultTaxRate());

        return [
            'quote' => [
                'subtotal' => $this->minorUnitsToDecimal($subtotalCents),
                'discount_value' => $this->normalDiscountValue($payload['discount_type'] ?? null, $payload['discount_value'] ?? 0),
                'discount_total' => $this->minorUnitsToDecimal($discountTotalCents),
                'tax_rate' => $this->minorUnitsToDecimal($this->decimalToMinorUnits($quoteTaxRate)),
                'tax_total' => $this->minorUnitsToDecimal($taxTotalCents),
                'grand_total' => $this->minorUnitsToDecimal($grandTotalCents),
            ],
            'items' => array_map(fn (array $item): array => array_diff_key($item, [
                'taxable_before_quote_discount_cents' => true,
                'tax_rate_hundredths' => true,
            ]), $items),
        ];
    }

    private function discountCents(int $baseCents, mixed $type, mixed $value): int
    {
        if ($type === 'percentage') {
            return min($baseCents, $this->percentageOf($baseCents, $this->decimalToMinorUnits($value)));
        }

        if ($type === 'fixed') {
            return min($baseCents, $this->decimalToMinorUnits($value));
        }

        return 0;
    }

    private function defaultTaxRate(): float
    {
        return $this->money?->defaultTaxRate() ?? (float) config('crm.money.default_tax_rate', 20);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function taxTotalAfterQuoteDiscount(array $items, int $quoteDiscountCents, int $baseAfterItemDiscountCents): int
    {
        $remainingQuoteDiscount = $quoteDiscountCents;
        $taxTotalCents = 0;
        $lastIndex = array_key_last($items);

        foreach ($items as $index => $item) {
            $baseCents = (int) $item['taxable_before_quote_discount_cents'];
            $discountShare = $index === $lastIndex
                ? $remainingQuoteDiscount
                : $this->roundedDivide($quoteDiscountCents * $baseCents, max(1, $baseAfterItemDiscountCents));

            $remainingQuoteDiscount -= $discountShare;
            $taxableCents = max(0, $baseCents - $discountShare);
            $taxTotalCents += $this->percentageOf($taxableCents, (int) $item['tax_rate_hundredths']);
        }

        return $taxTotalCents;
    }

    private function percentageOf(int $amountCents, int $percentHundredths): int
    {
        return $this->roundedDivide($amountCents * $percentHundredths, 10000);
    }

    private function roundedDivide(int $value, int $divisor): int
    {
        return intdiv($value + intdiv($divisor, 2), $divisor);
    }

    private function normalDiscountValue(mixed $type, mixed $value): string
    {
        if (! in_array($type, ['fixed', 'percentage'], true)) {
            return '0.00';
        }

        return $this->minorUnitsToDecimal($this->decimalToMinorUnits($value));
    }

    private function decimalToMinorUnits(mixed $value, int $scale = 2): int
    {
        $text = trim(str_replace(',', '.', (string) $value));

        if ($text === '') {
            return 0;
        }

        $negative = str_starts_with($text, '-');
        $text = ltrim($text, '+-');
        [$whole, $fraction] = array_pad(explode('.', $text, 2), 2, '');
        $whole = preg_replace('/\D/', '', $whole) ?: '0';
        $fraction = preg_replace('/\D/', '', $fraction) ?: '';
        $fraction = str_pad($fraction, $scale + 1, '0');
        $minor = ((int) $whole) * (10 ** $scale) + (int) substr($fraction, 0, $scale);

        if ((int) $fraction[$scale] >= 5) {
            $minor++;
        }

        return $negative ? -$minor : $minor;
    }

    private function minorUnitsToDecimal(int $minor, int $scale = 2): string
    {
        $negative = $minor < 0;
        $minor = abs($minor);
        $factor = 10 ** $scale;
        $whole = intdiv($minor, $factor);
        $fraction = str_pad((string) ($minor % $factor), $scale, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '').$whole.'.'.$fraction;
    }
}
