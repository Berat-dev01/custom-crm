<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmDoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_passes_on_healthy_installation(): void
    {
        $this->seed(CrmPermissionSeeder::class);
        User::factory()->create();

        $this->artisan('crm:doctor')
            ->expectsOutputToContain('All checks passed.')
            ->assertSuccessful();
    }

    public function test_doctor_fails_without_permissions_or_users(): void
    {
        $this->artisan('crm:doctor')->assertFailed();
    }
}
