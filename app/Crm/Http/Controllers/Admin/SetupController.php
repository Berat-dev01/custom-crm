<?php

namespace App\Crm\Http\Controllers\Admin;

use App\Crm\Models\CrmSetting;
use App\Crm\Models\DealStage;
use App\Crm\Services\Settings\CrmSettingsManager;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SetupController extends Controller
{
    public function index(CrmSettingsManager $settings): View
    {
        Gate::authorize('crm.settings.manage');

        $steps = $this->steps($settings);

        return view('crm::admin.setup.index', [
            'steps' => $steps,
            'completed' => collect($steps)->where('done', true)->count(),
            'total' => count($steps),
        ]);
    }

    /**
     * @return list<array{key: string, title: string, description: string, done: bool, url: string, action: string}>
     */
    private function steps(CrmSettingsManager $settings): array
    {
        $companyConfigured = CrmSetting::query()
            ->whereNull('organization_id')
            ->where('key', 'company_name')
            ->exists();

        $logoUploaded = (bool) $settings->get('company_logo_path');
        $stagesReady = DealStage::query()->count() > 0
            && DealStage::query()->where('is_won', true)->exists()
            && DealStage::query()->where('is_lost', true)->exists();
        $teamInvited = User::query()->where('is_active', true)->count() > 1;
        $mailConfigured = config('mail.default') !== 'log'
            && (string) config('mail.from.address') !== ''
            && ! str_contains((string) config('mail.mailers.smtp.host', ''), 'mailpit');
        $twoFactorAdopted = User::query()->whereNotNull('two_factor_confirmed_at')->exists();

        $steps = [
            [
                'key' => 'company',
                'title' => __('Company profile'),
                'description' => __('Set your company name, contact details, currency, VAT rate and quote prefix. These appear on quotes and PDFs.'),
                'done' => $companyConfigured,
                'url' => route('crm.settings.index'),
                'action' => __('Open Settings'),
            ],
            [
                'key' => 'logo',
                'title' => __('Company logo'),
                'description' => __('Upload your logo so quotes, PDFs and the customer quote page carry your brand.'),
                'done' => $logoUploaded,
                'url' => route('crm.settings.index'),
                'action' => __('Upload logo'),
            ],
            [
                'key' => 'stages',
                'title' => __('Sales pipeline stages'),
                'description' => __('Review the deal stages and make sure you have at least one won and one lost stage that match your sales process.'),
                'done' => $stagesReady,
                'url' => route('crm.deal-stages.index'),
                'action' => __('Review stages'),
            ],
            [
                'key' => 'mail',
                'title' => __('Outgoing email (SMTP)'),
                'description' => __('Configure MAIL_* in your .env so notifications and customer quote emails can be delivered. See docs/installation.md.'),
                'done' => $mailConfigured,
                'url' => route('crm.settings.index'),
                'action' => __('Check notification switches'),
            ],
            [
                'key' => 'team',
                'title' => __('Invite your team'),
                'description' => __('Create user accounts with the right roles (manager, sales, support, viewer).'),
                'done' => $teamInvited,
                'url' => route('crm.users.index'),
                'action' => __('Manage users'),
            ],
        ];

        if (config('crm.features.two_factor')) {
            $steps[] = [
                'key' => '2fa',
                'title' => __('Two-factor authentication'),
                'description' => __('Protect at least the owner account with TOTP two-factor authentication.'),
                'done' => $twoFactorAdopted,
                'url' => route('crm.security.index'),
                'action' => __('Open Security'),
            ];
        }

        return $steps;
    }
}
