<?php

namespace App\Crm\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Crm\Services\Notifications\NotificationPreferences;

class WeeklyDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const EMAIL_PREFERENCE_KEY = 'weekly_digest';

    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(public readonly array $summary) {}

    /**
     * Digest is email-only; it has no in-app counterpart.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return app(NotificationPreferences::class)->emailEnabledFor($notifiable, self::EMAIL_PREFERENCE_KEY)
            ? ['mail']
            : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->summary;

        return (new MailMessage)
            ->subject(trans('crm::notifications.weekly_digest.subject'))
            ->greeting(trans('crm::notifications.mail.greeting', ['name' => $notifiable->name ?? '']))
            ->line(trans('crm::notifications.weekly_digest.intro'))
            ->line(trans('crm::notifications.weekly_digest.pipeline', [
                'count' => $s['open_deals'],
                'value' => $s['open_pipeline_value'],
            ]))
            ->line(trans('crm::notifications.weekly_digest.won', [
                'count' => $s['won_deals'],
                'value' => $s['won_value'],
            ]))
            ->line(trans('crm::notifications.weekly_digest.lost', ['count' => $s['lost_deals']]))
            ->line(trans('crm::notifications.weekly_digest.overdue_tasks', ['count' => $s['overdue_tasks']]))
            ->line(trans('crm::notifications.weekly_digest.pending_quotes', ['count' => $s['pending_quotes']]))
            ->action(trans('crm::notifications.mail.action'), route('crm.dashboard'))
            ->line(trans('crm::notifications.mail.footer'));
    }
}
