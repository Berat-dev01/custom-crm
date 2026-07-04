<?php

namespace App\Crm\Http\Controllers\Api;

use App\Crm\Actions\Deals\MoveDealToStage;
use App\Crm\Actions\Deals\UpsertDeal;
use App\Crm\Http\Requests\Deals\MoveDealRequest;
use App\Crm\Http\Requests\Deals\StoreDealRequest;
use App\Crm\Http\Requests\Deals\UpdateDealRequest;
use App\Crm\Http\Resources\Api\DealResource;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Services\Deals\DealQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class DealsController extends Controller
{
    public function __construct(private readonly DealQuery $deals) {}

    public function index(Request $request): mixed
    {
        Gate::authorize('viewAny', Deal::class);
        $this->validateIndex($request);

        return DealResource::collection($this->deals->paginate($request));
    }

    public function store(StoreDealRequest $request, UpsertDeal $upsert): mixed
    {
        $deal = $upsert->handle(new Deal, $request->payload(), $request->user());

        return (new DealResource($deal->load(['stage', 'company', 'contact', 'owner', 'tags'])))
            ->additional(['message' => trans('crm::messages.deals.created')])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Deal $deal): DealResource
    {
        Gate::authorize('view', $deal);

        return new DealResource($deal->load(['stage', 'company', 'contact', 'owner', 'tags'])->loadCount([
            'tasks as open_tasks_count' => fn ($query) => $query->whereNull('completed_at'),
        ]));
    }

    public function update(UpdateDealRequest $request, Deal $deal, UpsertDeal $upsert): DealResource
    {
        $deal = $upsert->handle($deal, $request->payload(), $request->user());

        return (new DealResource($deal->load(['stage', 'company', 'contact', 'owner', 'tags'])))
            ->additional(['message' => trans('crm::messages.deals.updated')]);
    }

    public function move(MoveDealRequest $request, Deal $deal, MoveDealToStage $moveDeal): DealResource
    {
        Gate::authorize('move', $deal);

        $deal = $moveDeal->handle(
            $deal,
            DealStage::query()->findOrFail($request->validated('stage_id')),
            $request->integer('position') ?: null,
            $request->validated('lost_reason'),
            $request->user()
        );

        return (new DealResource($deal->load(['stage', 'company', 'contact', 'owner', 'tags'])))
            ->additional(['message' => trans('crm::messages.deals.moved')]);
    }

    public function destroy(Deal $deal): mixed
    {
        Gate::authorize('delete', $deal);

        $deal->delete();

        return response()->json(['message' => trans('crm::messages.deals.deleted')]);
    }

    private function validateIndex(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'status' => ['nullable', 'string', 'in:open,won,lost'],
            'expected_from' => ['nullable', 'date'],
            'expected_to' => ['nullable', 'date'],
            'value_min' => ['nullable', 'numeric', 'min:0'],
            'value_max' => ['nullable', 'numeric', 'min:0'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('crm.api.max_per_page', 100)],
        ]);
    }
}
