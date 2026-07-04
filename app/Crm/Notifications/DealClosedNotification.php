<?php

namespace App\Crm\Notifications;

use App\Crm\Models\Deal;
use App\Crm\Notifications\Concerns\RoutesEmailByPreference;
use App\Crm\Services\Configuration\MoneySettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesEmailByPreference;

    public const EMAIL_PREFERENCE_KEY = 'deal_closed';

    public function __construct(
        public readonly Deal $deal,
        public readonly string $result
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject($data['title'])
            ->greeting(trans('crm::notifications.mail.greeting', ['name' => $notifiable->name ?? '']))
            ->line($data['body'])
            ->action(trans('crm::notifications.mail.action'), $data['url'])
            ->line(trans('crm::notifications.mail.footer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $key = $this->result === 'won' ? 'won' : 'lost';

        return [
            'kind' => 'deal_'.$key,
            'deal_id' => $this->deal->id,
            'title' => trans('crm::notifications.deal_closed.'.$key.'_title'),
            'body' => trans('crm::notifications.deal_closed.body', [
                'deal' => $this->deal->title,
                'value' => number_format((float) $this->deal->value, 2).' '.app(MoneySettings::class)->defaultCurrency(),
            ]),
            'result' => $this->result,
            'url' => route('crm.deals.show', $this->deal),
        ];
    }
}
