<?php

namespace App\Crm\Notifications;

use App\Crm\Models\Quote;
use App\Crm\Notifications\Concerns\RoutesEmailByPreference;
use App\Crm\Support\CrmLabelCatalog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesEmailByPreference;

    public const EMAIL_PREFERENCE_KEY = 'quote_status_changes';

    public function __construct(
        public readonly Quote $quote,
        public readonly string $status
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
        $statusLabel = app(CrmLabelCatalog::class)->status($this->status);
        $body = $this->quote->company?->name
            ? trans('crm::notifications.quote_status_changed.body_with_company', [
                'quote' => $this->quote->quote_number,
                'company' => $this->quote->company->name,
            ])
            : trans('crm::notifications.quote_status_changed.body_without_company', [
                'quote' => $this->quote->quote_number,
            ]);

        return [
            'kind' => 'quote_'.$this->status,
            'quote_id' => $this->quote->id,
            'title' => trans('crm::notifications.quote_status_changed.title', ['status' => $statusLabel]),
            'body' => $body,
            'status' => $this->status,
            'url' => route('crm.quotes.show', $this->quote),
        ];
    }
}
