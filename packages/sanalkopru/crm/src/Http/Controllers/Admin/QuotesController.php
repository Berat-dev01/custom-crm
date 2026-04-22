<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Sanalkopru\Crm\Actions\Quotes\AcceptQuote;
use Sanalkopru\Crm\Actions\Quotes\DuplicateQuote;
use Sanalkopru\Crm\Actions\Quotes\ExpireQuote;
use Sanalkopru\Crm\Actions\Quotes\RejectQuote;
use Sanalkopru\Crm\Actions\Quotes\SendQuote;
use Sanalkopru\Crm\Actions\Quotes\UpsertQuote;
use Sanalkopru\Crm\Http\Requests\Quotes\AcceptQuoteRequest;
use Sanalkopru\Crm\Http\Requests\Quotes\StoreQuoteRequest;
use Sanalkopru\Crm\Http\Requests\Quotes\UpdateQuoteRequest;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\Tag;
use Sanalkopru\Crm\Services\Ai\AiDriverManager;
use Sanalkopru\Crm\Services\Quotes\QuotePdfRenderer;
use Sanalkopru\Crm\Services\Quotes\QuoteQuery;
use Symfony\Component\HttpFoundation\Response;

class QuotesController extends Controller
{
    public function __construct(private readonly QuoteQuery $quotes) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Quote::class);
        $this->validateIndex($request);

        return view('crm::admin.quotes.index', [
            'quotes' => $this->quotes->paginate($request),
            'filters' => $this->quotes->filters($request),
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Quote::class);

        return view('crm::admin.quotes.form', $this->formData(new Quote));
    }

    public function store(StoreQuoteRequest $request, UpsertQuote $upsert): RedirectResponse
    {
        $quote = $upsert->handle(new Quote, $request->payload(), $request->user());

        return redirect()
            ->route('crm.quotes.show', $quote)
            ->with('crm_status', 'Quote created.');
    }

    public function show(Quote $quote): View
    {
        Gate::authorize('view', $quote);

        return view('crm::admin.quotes.show', [
            'quote' => $this->loadQuote($quote),
            'aiAvailable' => app(AiDriverManager::class)->available(),
        ]);
    }

    public function edit(Quote $quote): View
    {
        Gate::authorize('update', $quote);

        return view('crm::admin.quotes.form', $this->formData($this->loadQuote($quote)));
    }

    public function update(UpdateQuoteRequest $request, Quote $quote, UpsertQuote $upsert): RedirectResponse
    {
        $quote = $upsert->handle($quote, $request->payload(), $request->user());

        return redirect()
            ->route('crm.quotes.show', $quote)
            ->with('crm_status', 'Quote updated.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        Gate::authorize('delete', $quote);

        $quote->delete();

        return redirect()
            ->route('crm.quotes.index')
            ->with('crm_status', 'Quote deleted.');
    }

    public function send(Quote $quote, SendQuote $send): RedirectResponse
    {
        Gate::authorize('send', $quote);
        $send->handle($quote, request()->user());

        return redirect()
            ->route('crm.quotes.show', $quote)
            ->with('crm_status', 'Quote marked as sent.');
    }

    public function accept(AcceptQuoteRequest $request, Quote $quote, AcceptQuote $accept): RedirectResponse
    {
        $accept->handle($quote, $request->boolean('mark_deal_won'), $request->user());

        return redirect()
            ->route('crm.quotes.show', $quote)
            ->with('crm_status', 'Quote accepted.');
    }

    public function reject(Quote $quote, RejectQuote $reject): RedirectResponse
    {
        Gate::authorize('reject', $quote);
        $reject->handle($quote, request()->user());

        return redirect()
            ->route('crm.quotes.show', $quote)
            ->with('crm_status', 'Quote rejected.');
    }

    public function expire(Quote $quote, ExpireQuote $expire): RedirectResponse
    {
        Gate::authorize('update', $quote);
        $expire->handle($quote, request()->user());

        return redirect()
            ->route('crm.quotes.show', $quote)
            ->with('crm_status', 'Quote expired.');
    }

    public function duplicate(Quote $quote, DuplicateQuote $duplicate): RedirectResponse
    {
        Gate::authorize('create', Quote::class);
        Gate::authorize('view', $quote);

        $newQuote = $duplicate->handle($this->loadQuote($quote), request()->user());

        return redirect()
            ->route('crm.quotes.edit', $newQuote)
            ->with('crm_status', 'Quote duplicated as a draft.');
    }

    public function preview(Quote $quote): View
    {
        Gate::authorize('export', $quote);
        $renderer = app(QuotePdfRenderer::class);

        return view('crm::admin.quotes.pdf', [
            'quote' => $this->loadQuote($quote),
            'company' => $renderer->companyProfile(),
            'logoPath' => $renderer->logoPath(),
        ]);
    }

    public function download(Quote $quote, QuotePdfRenderer $renderer): Response
    {
        Gate::authorize('export', $quote);
        $quote = $this->loadQuote($quote);

        return response($renderer->render($quote), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$renderer->filename($quote).'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Quote $quote): array
    {
        return [
            'quote' => $quote,
            'contacts' => Contact::query()->orderBy('full_name')->limit(250)->get(['id', 'full_name']),
            'companies' => Company::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'deals' => Deal::query()->orderByDesc('updated_at')->limit(250)->get(['id', 'title']),
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
            'selectedTags' => $quote->exists ? $quote->tags()->pluck('tags.id')->all() : [],
            'statuses' => $this->statuses(),
            'discountTypes' => $this->discountTypes(),
            'currencies' => array_combine(
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR']),
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR'])
            ),
            'defaultTaxRate' => config('crm.money.default_tax_rate', 20),
        ];
    }

    private function loadQuote(Quote $quote): Quote
    {
        return $quote->load([
            'contact',
            'company',
            'deal',
            'owner',
            'tags',
            'items' => fn ($query) => $query->orderBy('position')->orderBy('id'),
            'activities.user',
            'tasks.assignee',
        ]);
    }

    private function validateIndex(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in(array_keys($this->statuses()))],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function discountTypes(): array
    {
        return [
            'fixed' => 'Fixed',
            'percentage' => 'Percentage',
        ];
    }
}
