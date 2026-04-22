<?php

namespace Sanalkopru\Crm\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'contact_id' => Contact::factory(),
            'company_id' => Company::factory(),
            'stage_id' => DealStage::factory(),
            'value' => fake()->randomFloat(2, 10000, 250000),
            'currency' => config('crm.money.default_currency', 'TRY'),
            'probability' => fake()->numberBetween(10, 90),
            'expected_close_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'status' => 'open',
            'owner_id' => User::factory(),
            'position' => fake()->numberBetween(1, 20),
            'custom_fields' => ['source' => 'factory'],
        ];
    }

    public function won(): static
    {
        return $this->state(fn (): array => [
            'status' => 'won',
            'probability' => 100,
            'closed_at' => now(),
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (): array => [
            'status' => 'lost',
            'probability' => 0,
            'closed_at' => now(),
            'lost_reason' => 'Budget mismatch',
        ]);
    }
}
