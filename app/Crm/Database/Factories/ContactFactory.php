<?php

namespace App\Crm\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => "{$firstName} {$lastName}",
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'title' => fake()->jobTitle(),
            'company_id' => Company::factory(),
            'lifecycle_stage' => fake()->randomElement(['lead', 'prospect', 'customer']),
            'source' => fake()->randomElement(['website', 'referral', 'event', 'outbound']),
            'owner_id' => User::factory(),
            'last_contacted_at' => fake()->optional()->dateTimeBetween('-30 days'),
            'custom_fields' => ['preferred_channel' => fake()->randomElement(['email', 'phone'])],
        ];
    }
}
