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
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assignRole('crm_owner');
        $this->call(CrmDemoSeeder::class);
    }
}
