<?php

namespace App\Crm\Database\Factories;

use App\Crm\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Hot Lead', 'VIP', 'Renewal', 'Enterprise', 'Follow Up', 'At Risk']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => fake()->hexColor(),
        ];
    }
}
