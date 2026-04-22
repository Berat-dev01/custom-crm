<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Sanalkopru\Crm\Database\Seeders\CrmDealStageSeeder;
use Sanalkopru\Crm\Database\Seeders\CrmDemoSeeder;
use Sanalkopru\Crm\Database\Seeders\CrmPermissionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(CrmPermissionSeeder::class);
        $this->call(CrmDealStageSeeder::class);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assignRole('crm_owner');

        $this->call(CrmDemoSeeder::class);
    }
}
