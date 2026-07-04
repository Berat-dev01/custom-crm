<?php

namespace App\Crm\Database\Factories;

use App\Crm\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'type' => fake()->randomElement(['note', 'call', 'email', 'meeting', 'system']),
            'user_id' => User::factory(),
            'occurred_at' => fake()->dateTimeBetween('-30 days'),
            'metadata' => ['source' => 'factory'],
        ];
    }
}
