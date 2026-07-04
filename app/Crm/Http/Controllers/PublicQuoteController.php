<?php

namespace App\Crm\Http\Controllers;

use App\Crm\Actions\Quotes\AcceptQuote;
use App\Crm\Actions\Quotes\RejectQuote;
use App\Crm\Models\Quote;
use App\Crm\Services\Activities\ActivityLogger;
use App\Crm\Services\Quotes\QuotePdfRenderer;
use App\Crm\Services\Settings\CrmSettingsManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class PublicQuoteController extends Controller
{
    public function show(string $token): View
    {
        $quote = $this->resolveQuote($token);

        return view('crm::public.quote', [
            'quote' => $quote->load(['contact', 'company', 'items']),
            'companyProfile' => app(CrmSettingsManager::class)->companyProfile(),
            'logoUrl' => app(CrmSettingsManager::class)->logoUrl(),
        ]);
    }

    public function accept(string $token, AcceptQuote $accept): RedirectResponse
    {
        $quote = $this->resolveQuote($token);

        if ($quote->status === Quote::STATUS_SENT) {
            $accept->handle($quote);
        }

        return redirect()
            ->route('crm.public.quote.show', $token)
            ->with('public_quote_status', trans('crm::public.quote.accepted_message'));
    }

    public function reject(Request $request, string $token, RejectQuote $reject, ActivityLogger $activities): RedirectResponse
    {
        $quote = $this->resolveQuote($token);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($quote->status === Quote::STATUS_SENT) {
            $reject->handle($quote);

            if (! empty($validated['reason'])) {
                $activities->manual($quote, [
                    'type' => 'note',
                    'subject' => trans('crm::public.quote.rejection_note_subject'),
                    'body' => $validated['reason'],
                ], null);
            }
        }

        return redirect()
            ->route('crm.public.quote.show', $token)
            ->with('public_quote_status', trans('crm::public.quote.rejected_message'));
    }

    public function download(string $token, QuotePdfRenderer $renderer): Response
    {
        $quote = $this->resolveQuote($token)
            ->load(['contact', 'company', 'deal', 'owner', 'items']);

        return response($renderer->render($quote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$renderer->filename($quote).'"',
        ]);
    }

    private function resolveQuote(string $token): Quote
    {
        abort_unless(strlen($token) >= 32, 404);

        return Quote::query()
            ->where('public_token', $token)
            ->firstOrFail();
    }
}
