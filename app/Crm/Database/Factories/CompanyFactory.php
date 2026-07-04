<?php

namespace App\Crm\Database\Factories;

use App\Crm\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'tax_number' => fake()->numerify('##########'),
            'tax_office' => fake()->city(),
            'address_line_1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => 'TR',
            'sector' => fake()->randomElement(['Technology', 'Retail', 'Manufacturing', 'Consulting']),
            'owner_id' => User::factory(),
            'custom_fields' => ['source' => 'demo'],
        ];
    }

    public function named(string $name): static
    {
        return $this->state(fn (): array => [
            'name' => $name,
        ]);
    }
}
