<?php

namespace Tests\Feature;

use App\Crm\Database\Seeders\CrmPermissionSeeder;
use App\Crm\Services\Security\TwoFactorService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class CrmTwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['crm.features.two_factor' => true]);

        $this->seed(CrmPermissionSeeder::class);
        $this->user = User::factory()->create(['password' => 'secret-password'])->assignRole('crm_owner');
    }

    private function enableTwoFactor(User $user): string
    {
        $secret = app(TwoFactorService::class)->generateSecret();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['AAAAA-BBBBB', 'CCCCC-DDDDD'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $secret;
    }

    public function test_user_can_enable_two_factor_with_valid_code(): void
    {
        $this->actingAs($this->user, 'admin')
            ->post(route('crm.security.2fa.enable'))
            ->assertRedirect(route('crm.security.index'));

        $secret = session('crm_2fa_pending_secret');
        $this->assertNotNull($secret);

        // Setup page shows QR + manual key
        $this->actingAs($this->user, 'admin')
            ->withSession(['crm_2fa_pending_secret' => $secret])
            ->get(route('crm.security.index'))
            ->assertOk()
            ->assertSee($secret);

        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->actingAs($this->user, 'admin')
            ->withSession(['crm_2fa_pending_secret' => $secret])
            ->post(route('crm.security.2fa.confirm'), ['code' => $code])
            ->assertRedirect(route('crm.security.index'))
            ->assertSessionHas('crm_2fa_recovery_codes');

        $this->assertTrue($this->user->refresh()->hasTwoFactorEnabled());
        $this->assertCount(8, $this->user->two_factor_recovery_codes);
    }

    public function test_invalid_confirmation_code_is_rejected(): void
    {
        $secret = app(TwoFactorService::class)->generateSecret();

        $this->actingAs($this->user, 'admin')
            ->withSession(['crm_2fa_pending_secret' => $secret])
            ->post(route('crm.security.2fa.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertFalse($this->user->refresh()->hasTwoFactorEnabled());
    }

    public function test_login_requires_totp_code_when_enabled(): void
    {
        $secret = $this->enableTwoFactor($this->user);

        // Correct credentials land on the challenge, not the dashboard.
        $this->post('/admin/login', [
            'email' => $this->user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.login.2fa'));

        $this->assertGuest('admin');

        // Wrong code is rejected.
        $this->post(route('admin.login.2fa.post'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest('admin');

        // Correct TOTP completes the login.
        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $this->post(route('admin.login.2fa.post'), ['code' => $code])
            ->assertRedirect(route('crm.dashboard'));

        $this->assertAuthenticatedAs($this->user, 'admin');
    }

    public function test_recovery_code_completes_login_and_is_consumed(): void
    {
        $this->enableTwoFactor($this->user);

        $this->post('/admin/login', [
            'email' => $this->user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.login.2fa'));

        $this->post(route('admin.login.2fa.post'), ['code' => 'AAAAA-BBBBB'])
            ->assertRedirect(route('crm.dashboard'));

        $this->assertAuthenticatedAs($this->user, 'admin');
        $this->assertSame(['CCCCC-DDDDD'], $this->user->refresh()->two_factor_recovery_codes);
    }

    public function test_challenge_page_requires_pending_login(): void
    {
        $this->get(route('admin.login.2fa'))->assertRedirect(route('admin.login'));
    }

    public function test_two_factor_can_be_disabled_with_password(): void
    {
        $this->enableTwoFactor($this->user);

        // Wrong password keeps 2FA on.
        $this->actingAs($this->user, 'admin')
            ->delete(route('crm.security.2fa.disable'), ['password' => 'wrong'])
            ->assertSessionHasErrors('password');
        $this->assertTrue($this->user->refresh()->hasTwoFactorEnabled());

        $this->actingAs($this->user, 'admin')
            ->delete(route('crm.security.2fa.disable'), ['password' => 'secret-password'])
            ->assertRedirect(route('crm.security.index'));

        $this->assertFalse($this->user->refresh()->hasTwoFactorEnabled());
    }

    public function test_login_without_two_factor_goes_straight_to_dashboard(): void
    {
        $this->post('/admin/login', [
            'email' => $this->user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('crm.dashboard'));

        $this->assertAuthenticatedAs($this->user, 'admin');
    }
}
