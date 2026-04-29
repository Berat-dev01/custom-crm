<?php

namespace App\Crm\Notifications;

use Illuminate\Notifications\Notification;
use App\Crm\Models\Quote;

class QuoteStatusChangedNotification extends Notification
{
    public function __construct(
        public readonly Quote $quote,
        public readonly string $status
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $statusLabel = app(\App\Crm\Support\CrmLabelCatalog::class)->status($this->status);
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
