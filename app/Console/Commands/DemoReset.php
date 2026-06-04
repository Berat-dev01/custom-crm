<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoReset extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset the demo database (only runs when APP_ENV=demo)';

    public function handle(): int
    {
        if (app()->environment() !== 'demo') {
            $this->error('demo:reset only runs when APP_ENV=demo. Current environment: '.app()->environment());

            return self::FAILURE;
        }

        $this->info('Resetting demo database…');

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->info('Demo reset complete.');

        return self::SUCCESS;
    }
}
