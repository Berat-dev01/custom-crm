<?php

namespace App\Crm\Mail;

use App\Crm\Models\Quote;
use App\Crm\Services\Quotes\QuotePdfRenderer;
use App\Crm\Services\Settings\CrmSettingsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteCustomerMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Quote $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: trans('crm::notifications.quote_customer.subject', [
                'quote' => $this->quote->quote_number,
                'company' => app(CrmSettingsManager::class)->get('company_name', config('app.name')),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'crm::mail.quotes.customer',
            with: [
                'quote' => $this->quote->loadMissing(['contact', 'company', 'items']),
                'companyProfile' => app(CrmSettingsManager::class)->companyProfile(),
            ],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        $renderer = app(QuotePdfRenderer::class);
        $quote = $this->quote->loadMissing(['contact', 'company', 'deal', 'owner', 'items']);

        return [
            Attachment::fromData(fn (): string => $renderer->render($quote), $renderer->filename($quote))
                ->withMime('application/pdf'),
        ];
    }
}
