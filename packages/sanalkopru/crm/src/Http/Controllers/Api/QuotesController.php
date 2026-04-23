<?php

namespace Sanalkopru\Crm\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Sanalkopru\Crm\Actions\Quotes\UpsertQuote;
use Sanalkopru\Crm\Http\Requests\Quotes\StoreQuoteRequest;
use Sanalkopru\Crm\Http\Requests\Quotes\UpdateQuoteRequest;
use Sanalkopru\Crm\Http\Resources\Api\QuoteResource;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Services\Quotes\QuoteQuery;

class QuotesController extends Controller
{
    public function __construct(private readonly QuoteQuery $quotes) {}

    public function index(Request $request): mixed
    {
        Gate::authorize('viewAny', Quote::class);
        $this->validateIndex($request);

        return QuoteResource::collection($this->quotes->paginate($request));
    }

    public function store(StoreQuoteRequest $request, UpsertQuote $upsert): mixed
    {
        $quote = $upsert->handle(new Quote, $request->payload(), $request->user());

        return (new QuoteResource($quote))
            ->additional(['message' => 'Quote created.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Quote $quote): QuoteResource
    {
        Gate::authorize('view', $quote);

        return new QuoteResource($quote->load(['company', 'contact', 'deal', 'owner', 'tags', 'items']));
    }

    public function update(UpdateQuoteRequest $request, Quote $quote, UpsertQuote $upsert): QuoteResource
    {
        $quote = $upsert->handle($quote, $request->payload(), $request->user());

        return (new QuoteResource($quote))
            ->additional(['message' => 'Quote updated.']);
    }

    private function validateIndex(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'sent', 'accepted', 'rejected', 'expired'])],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('crm.api.max_per_page', 100)],
        ]);
    }
}
