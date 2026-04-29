<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Crm\Services\Quotes\QuoteCalculator;

class QuoteCalculatorTest extends TestCase
{
    public function test_it_calculates_fixed_and_percentage_discounts_with_tax(): void
    {
        $result = (new QuoteCalculator)->calculate([
            'discount_type' => 'fixed',
            'discount_value' => '100',
            'items' => [
                [
                    'name' => 'Implementation',
                    'quantity' => '2',
                    'unit_price' => '1000',
                    'discount_type' => 'percentage',
                    'discount_value' => '10',
                    'tax_rate' => '20',
                    'position' => 1,
                ],
                [
                    'name' => 'Support',
                    'quantity' => '1',
                    'unit_price' => '500',
                    'discount_type' => 'fixed',
                    'discount_value' => '50',
                    'tax_rate' => '10',
                    'position' => 2,
                ],
            ],
        ]);

        $this->assertSame('2500.00', $result['quote']['subtotal']);
        $this->assertSame('350.00', $result['quote']['discount_total']);
        $this->assertSame('387.00', $result['quote']['tax_total']);
        $this->assertSame('2537.00', $result['quote']['grand_total']);
        $this->assertSame('2160.00', $result['items'][0]['line_total']);
        $this->assertSame('495.00', $result['items'][1]['line_total']);
    }

    public function test_it_rounds_quantity_prices_to_money_decimals(): void
    {
        $result = (new QuoteCalculator)->calculate([
            'items' => [
                [
                    'name' => 'Fractional service',
                    'quantity' => '1.555',
                    'unit_price' => '99.995',
                    'tax_rate' => '20',
                    'position' => 1,
                ],
            ],
        ]);

        $this->assertSame('155.50', $result['quote']['subtotal']);
        $this->assertSame('31.10', $result['quote']['tax_total']);
        $this->assertSame('186.60', $result['quote']['grand_total']);
    }
}
