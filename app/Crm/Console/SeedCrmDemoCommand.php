<?php

namespace App\Crm\Console;

use Illuminate\Console\Command;
use App\Crm\Database\Seeders\CrmDemoSeeder;

class SeedCrmDemoCommand extends Command
{
    protected $signature = 'crm:seed-demo';

    protected $description = 'Seed CRM permissions, stages, demo users, and demo CRM data.';

    public function handle(): int
    {
        $this->call(CrmDemoSeeder::class, ['--force' => true]);
        $this->info('CRM demo data seeded.');

        return self::SUCCESS;
    }
}
