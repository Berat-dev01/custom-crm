<?php

namespace App\Crm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Crm\Models\DealStage;

/**
 * @extends Factory<DealStage>
 */
class DealStageFactory extends Factory
{
    protected $model = DealStage::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['New', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->hexColor(),
            'position' => fake()->numberBetween(1, 10),
            'probability' => fake()->numberBetween(0, 100),
            'is_won' => $name === 'Won',
            'is_lost' => $name === 'Lost',
        ];
    }

    public function won(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Won',
            'slug' => 'won',
            'probability' => 100,
            'is_won' => true,
            'is_lost' => false,
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Lost',
            'slug' => 'lost',
            'probability' => 0,
            'is_won' => false,
            'is_lost' => true,
        ]);
    }
}
