<?php

namespace Sanalkopru\Crm\Notifications;

use Illuminate\Notifications\Notification;
use Sanalkopru\Crm\Models\Quote;

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
        $label = ucfirst($this->status);
        $body = $this->quote->quote_number;

        if ($this->quote->company?->name) {
            $body .= ' - '.$this->quote->company->name;
        }

        return [
            'kind' => 'quote_'.$this->status,
            'quote_id' => $this->quote->id,
            'title' => 'Quote '.$label,
            'body' => $body,
            'status' => $this->status,
            'url' => route('crm.quotes.show', $this->quote),
        ];
    }
}
