<?php

namespace App\Crm\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrmDoctorCommand extends Command
{
    protected $signature = 'crm:doctor';

    protected $description = 'Run post-installation health checks for the CRM.';

    /**
     * @var list<array{label: string, ok: bool, detail: string}>
     */
    private array $results = [];

    public function handle(): int
    {
        $this->checkDatabase();
        $this->checkMigrations();
        $this->checkCache();
        $this->checkStorageLink();
        $this->checkQueue();
        $this->checkMail();
        $this->checkPermissions();
        $this->checkAdminUser();
        $this->checkRoutes();
        $this->checkAppKey();

        $this->table(
            ['Check', 'Status', 'Detail'],
            collect($this->results)->map(fn (array $row): array => [
                $row['label'],
                $row['ok'] ? '<info>OK</info>' : '<error>FAIL</error>',
                $row['detail'],
            ])->all()
        );

        $failed = collect($this->results)->where('ok', false)->count();

        if ($failed > 0) {
            $this->error("{$failed} check(s) failed.");

            return self::FAILURE;
        }

        $this->info('All checks passed.');

        return self::SUCCESS;
    }

    private function record(string $label, bool $ok, string $detail = ''): void
    {
        $this->results[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
            $this->record('Database connection', true, DB::connection()->getDriverName());
        } catch (\Throwable $e) {
            $this->record('Database connection', false, Str::limit($e->getMessage(), 80));
        }
    }

    private function checkMigrations(): void
    {
        try {
            $pending = ! Schema::hasTable('migrations');

            if (! $pending) {
                $ran = DB::table('migrations')->count();
                $files = count(glob(database_path('migrations/*.php')) ?: []);
                $pending = $ran < $files;
                $this->record('Migrations', ! $pending, $pending ? "ran {$ran}/{$files}" : "{$ran} applied");

                return;
            }

            $this->record('Migrations', false, 'migrations table missing — run php artisan migrate');
        } catch (\Throwable $e) {
            $this->record('Migrations', false, Str::limit($e->getMessage(), 80));
        }
    }

    private function checkCache(): void
    {
        try {
            $key = 'crm_doctor_'.Str::random(8);
            Cache::put($key, 'ok', 10);
            $ok = Cache::pull($key) === 'ok';
            $this->record('Cache store', $ok, (string) config('cache.default'));
        } catch (\Throwable $e) {
            $this->record('Cache store', false, Str::limit($e->getMessage(), 80));
        }
    }

    private function checkStorageLink(): void
    {
        $ok = is_link(public_path('storage')) || is_dir(public_path('storage'));
        $this->record('Storage link', $ok, $ok ? 'public/storage present' : 'run php artisan storage:link');
    }

    private function checkQueue(): void
    {
        $connection = (string) config('queue.default');
        $ok = $connection !== '';
        $detail = $connection;

        if ($connection === 'sync') {
            $detail = 'sync — emails/webhooks run inline; use redis/database + a worker in production';
        }

        $this->record('Queue connection', $ok, $detail);
    }

    private function checkMail(): void
    {
        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');
        $ok = $mailer !== '' && $from !== '';
        $this->record('Mail configuration', $ok, "{$mailer} / from: {$from}");
    }

    private function checkPermissions(): void
    {
        try {
            $ok = Schema::hasTable('permissions')
                && DB::table('permissions')->where('name', 'like', 'crm.%')->exists();
            $this->record('CRM permissions seeded', $ok, $ok ? '' : 'run php artisan db:seed --class="App\\\\Crm\\\\Database\\\\Seeders\\\\CrmPermissionSeeder"');
        } catch (\Throwable $e) {
            $this->record('CRM permissions seeded', false, Str::limit($e->getMessage(), 80));
        }
    }

    private function checkAdminUser(): void
    {
        try {
            $count = User::query()->where('is_active', true)->count();
            $this->record('Active users', $count > 0, "{$count} active user(s)");
        } catch (\Throwable $e) {
            $this->record('Active users', false, Str::limit($e->getMessage(), 80));
        }
    }

    private function checkRoutes(): void
    {
        $ok = Route::has('crm.dashboard') && Route::has('crm.api.health');
        $this->record('CRM routes registered', $ok, $ok ? 'crm.dashboard, crm.api.health' : 'route registration failed');
    }

    private function checkAppKey(): void
    {
        $ok = (string) config('app.key') !== '';
        $this->record('APP_KEY', $ok, $ok ? 'set' : 'run php artisan key:generate');
    }
}
