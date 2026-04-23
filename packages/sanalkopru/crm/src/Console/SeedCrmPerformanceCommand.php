<?php

namespace Sanalkopru\Crm\Console;

use Illuminate\Console\Command;
use Sanalkopru\Crm\Database\Seeders\CrmPerformanceSeeder;

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
