<?php

namespace Database\Seeders;

use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmDemoSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CrmPermissionSeeder::class);
        $this->call(CrmDealStageSeeder::class);
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@webakil.com',
            'password' => bcrypt('demo1234'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('crm_owner');
        $this->call(CrmDemoSeeder::class);
    }
}
