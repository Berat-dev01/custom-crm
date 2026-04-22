<?php

namespace Sanalkopru\Crm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\QuoteItem;

/**
 * @extends Factory<QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    protected $model = QuoteItem::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 10);
        $unitPrice = fake()->randomFloat(2, 500, 25000);
        $taxRate = (float) config('crm.money.default_tax_rate', 20);
        $lineSubtotal = $quantity * $unitPrice;
        $lineTotal = round($lineSubtotal + ($lineSubtotal * ($taxRate / 100)), 2);

        return [
            'quote_id' => Quote::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_type' => null,
            'discount_value' => 0,
            'tax_rate' => $taxRate,
            'line_total' => $lineTotal,
            'position' => fake()->numberBetween(1, 10),
        ];
    }
}
