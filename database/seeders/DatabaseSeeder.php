<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmDemoSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CrmPermissionSeeder::class);
        $this->call(CrmDealStageSeeder::class);
        $user = \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'admin@webakil.com',
            'password' => bcrypt('demo1234'),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('crm_owner');
        $this->call(CrmDemoSeeder::class);
    }
}
