<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Crm\Database\Seeders\CrmDealStageSeeder;
use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Notifications\WeeklyDigestNotification;
use Tests\TestCase;

class CrmWeeklyDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CrmPermissionSeeder::class);
        $this->seed(CrmDealStageSeeder::class);
    }

    public function test_digest_goes_to_owners_and_managers_only(): void
    {
        Notification::fake();

        $owner = User::factory()->create()->assignRole('crm_owner');
        $manager = User::factory()->create()->assignRole('crm_manager');
        $sales = User::factory()->create()->assignRole('crm_sales');

        $stage = DealStage::query()->where('is_won', false)->where('is_lost', false)->ordered()->firstOrFail();
        Deal::factory()->create(['stage_id' => $stage->id, 'status' => 'open', 'value' => 1500]);

        $this->artisan('crm:digest:send-weekly')
            ->expectsOutputToContain('Sent 2 weekly CRM digest email(s).')
            ->assertSuccessful();

        Notification::assertSentTo(
            $owner,
            WeeklyDigestNotification::class,
            fn (WeeklyDigestNotification $notification, array $channels): bool => $channels === ['mail']
                && $notification->summary['open_deals'] === 1
        );
        Notification::assertSentTo($manager, WeeklyDigestNotification::class);
        Notification::assertNotSentTo($sales, WeeklyDigestNotification::class);
    }

    public function test_digest_respects_user_opt_out(): void
    {
        Notification::fake();

        $owner = User::factory()->create()->assignRole('crm_owner');
        $owner->forceFill(['notification_email_prefs' => ['weekly_digest' => false]])->save();

        $this->artisan('crm:digest:send-weekly')->assertSuccessful();

        // via() resolves to no channels for opted-out users, so nothing is delivered.
        Notification::assertNotSentTo($owner, WeeklyDigestNotification::class);
    }

    public function test_digest_skips_when_globally_disabled(): void
    {
        Notification::fake();

        User::factory()->create()->assignRole('crm_owner');

        \App\Crm\Models\CrmSetting::query()->create([
            'organization_id' => null,
            'key' => 'notify_weekly_digest',
            'group' => 'notifications',
            'value' => ['value' => false],
            'type' => 'boolean',
            'is_encrypted' => false,
        ]);
        \Illuminate\Support\Facades\Cache::flush();

        $this->artisan('crm:digest:send-weekly')
            ->expectsOutputToContain('Sent 0 weekly CRM digest email(s).')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}
