<?php

namespace App\Crm\Console;

use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use App\Crm\Models\Task;
use App\Crm\Notifications\WeeklyDigestNotification;
use App\Crm\Services\Configuration\MoneySettings;
use App\Crm\Services\Notifications\NotificationPreferences;
use App\Models\User;
use Illuminate\Console\Command;

class SendWeeklyDigestCommand extends Command
{
    protected $signature = 'crm:digest:send-weekly';

    protected $description = 'Email the weekly CRM digest to owners and managers.';

    public function handle(NotificationPreferences $preferences, MoneySettings $money): int
    {
        if (! $preferences->weeklyDigestEnabled() || ! $preferences->emailChannelEnabled()) {
            $this->info('Sent 0 weekly CRM digest email(s).');

            return self::SUCCESS;
        }

        $summary = $this->buildSummary($money);

        $recipients = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->role(['crm_owner', 'crm_manager'])
            ->get();

        $sent = 0;

        foreach ($recipients as $recipient) {
            $recipient->notify(new WeeklyDigestNotification($summary));
            $sent++;
        }

        $this->info("Sent {$sent} weekly CRM digest email(s).");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(MoneySettings $money): array
    {
        $since = now()->subDays(7);
        $currency = $money->defaultCurrency();

        $wonDeals = Deal::query()->where('status', 'won')->where('closed_at', '>=', $since);
        $format = fn (float $value): string => number_format($value, 2).' '.$currency;

        return [
            'open_deals' => Deal::query()->where('status', 'open')->count(),
            'open_pipeline_value' => $format((float) Deal::query()->where('status', 'open')->sum('value')),
            'won_deals' => (clone $wonDeals)->count(),
            'won_value' => $format((float) (clone $wonDeals)->sum('value')),
            'lost_deals' => Deal::query()->where('status', 'lost')->where('closed_at', '>=', $since)->count(),
            'overdue_tasks' => Task::query()->overdue()->count(),
            'pending_quotes' => Quote::query()->where('status', 'sent')->count(),
        ];
    }
}
