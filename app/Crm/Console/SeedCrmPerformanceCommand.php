<?php

namespace App\Crm\Console;

use App\Crm\Database\Seeders\CrmPerformanceSeeder;
use Illuminate\Console\Command;

class SeedCrmPerformanceCommand extends Command
{
    protected $signature = 'crm:seed-performance';

    protected $description = 'Seed the CRM performance dataset.';

    public function handle(): int
    {
        $this->call(CrmPerformanceSeeder::class, ['--force' => true]);
        $this->info('CRM performance data seeded.');

        return self::SUCCESS;
    }
}
