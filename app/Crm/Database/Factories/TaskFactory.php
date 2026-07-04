<?php

namespace App\Crm\Database\Factories;

use App\Crm\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $dueAt = fake()->dateTimeBetween('+1 day', '+14 days');

        return [
            'title' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'assigned_to' => User::factory(),
            'due_at' => $dueAt,
            'reminder_at' => fake()->optional()->dateTimeBetween('now', $dueAt),
            'priority' => fake()->randomElement(['low', 'normal', 'high', 'urgent']),
            'status' => 'open',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
