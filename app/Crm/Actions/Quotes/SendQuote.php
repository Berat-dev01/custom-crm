<?php

namespace App\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Mail;
use App\Crm\Events\QuoteSent;
use App\Crm\Mail\QuoteCustomerMail;
use App\Crm\Services\Notifications\NotificationPreferences;
use App\Crm\Models\Quote;
use App\Crm\Services\Audit\CrmAuditLogger;
use App\Crm\Services\Notifications\CrmBusinessNotifier;
use App\Crm\Services\Webhooks\CrmWebhookDispatcher;

class SendQuote
{
    public function __construct(
        private readonly CrmAuditLogger $audit,
        private readonly CrmBusinessNotifier $notifications,
        private readonly CrmWebhookDispatcher $webhooks
    ) {}

    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        if ($quote->status === Quote::STATUS_SENT) {
            return $quote;
        }

        $quote->assertCanTransitionTo(Quote::STATUS_SENT);

        $before = $quote->only(['status', 'sent_at']);
        $statusChanged = ($before['status'] ?? null) !== 'sent';

        $quote->forceFill([
            'status' => 'sent',
            'sent_at' => $quote->sent_at ?: now(),
            'updated_by' => $user?->getAuthIdentifier(),
        ])->save();

        event(new QuoteSent($quote->refresh(), $user));
        $this->mailQuoteToCustomer($quote);
        $this->audit->record('crm.quote.sent', $quote, $user, $before, $quote->only(['status', 'sent_at']));

        if ($statusChanged) {
            $this->notifications->quoteStatusChanged($quote->fresh(['owner', 'company', 'deal.owner']), 'sent', $user);
            $this->webhooks->dispatch('quote.sent', $quote);
        }

        return $quote;
    }

    private function mailQuoteToCustomer(Quote $quote): void
    {
        if (! app(NotificationPreferences::class)->emailChannelEnabled()) {
            return;
        }

        $quote->loadMissing(['contact', 'company']);
        $email = $quote->contact?->email ?: $quote->company?->email;

        if (! $email) {
            return;
        }

        Mail::to($email)->queue(new QuoteCustomerMail($quote));
    }
}
