<?php

namespace Sanalkopru\Crm\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000, 200000);
        $taxRate = (float) config('crm.money.default_tax_rate', 20);
        $taxTotal = round($subtotal * ($taxRate / 100), 2);

        return [
            'quote_number' => config('crm.quotes.number_prefix', 'CRM-').fake()->unique()->numerify('######'),
            'contact_id' => Contact::factory(),
            'company_id' => Company::factory(),
            'deal_id' => Deal::factory(),
            'status' => fake()->randomElement(['draft', 'sent']),
            'currency' => config('crm.money.default_currency', 'TRY'),
            'subtotal' => $subtotal,
            'discount_type' => null,
            'discount_value' => 0,
            'discount_total' => 0,
            'tax_rate' => $taxRate,
            'tax_total' => $taxTotal,
            'grand_total' => $subtotal + $taxTotal,
            'valid_until' => now()->addDays(30)->toDateString(),
            'notes' => fake()->optional()->sentence(),
            'terms' => 'Payment due within 14 days.',
            'owner_id' => User::factory(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }
}
