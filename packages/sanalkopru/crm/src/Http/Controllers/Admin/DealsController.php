<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Actions\Deals\AddDealActivity;
use Sanalkopru\Crm\Actions\Deals\AddDealTask;
use Sanalkopru\Crm\Actions\Deals\CreateDealQuote;
use Sanalkopru\Crm\Actions\Deals\MoveDealToStage;
use Sanalkopru\Crm\Actions\Deals\UpsertDeal;
use Sanalkopru\Crm\Http\Requests\Deals\CloseDealAsLostRequest;
use Sanalkopru\Crm\Http\Requests\Deals\MoveDealRequest;
use Sanalkopru\Crm\Http\Requests\Deals\StoreDealActivityRequest;
use Sanalkopru\Crm\Http\Requests\Deals\StoreDealQuoteRequest;
use Sanalkopru\Crm\Http\Requests\Deals\StoreDealRequest;
use Sanalkopru\Crm\Http\Requests\Deals\StoreDealTaskRequest;
use Sanalkopru\Crm\Http\Requests\Deals\UpdateDealRequest;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\SavedFilter;
use Sanalkopru\Crm\Models\Tag;
use Sanalkopru\Crm\Services\Ai\AiDriverManager;
use Sanalkopru\Crm\Services\Configuration\MoneySettings;
use Sanalkopru\Crm\Services\Deals\DealQuery;
use Sanalkopru\Crm\Support\CrmExportSchema;
use Sanalkopru\Crm\Support\CrmFormatter;
use Sanalkopru\Crm\Support\CrmLabelCatalog;

class DealsController extends Controller
{
    public function __construct(
        private readonly DealQuery $deals,
        private readonly CrmLabelCatalog $labels
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.deals.view');

        $request->validate([
            'view' => ['nullable', 'string', 'in:kanban,list'],
            'search' => ['nullable', 'string', 'max:120'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'status' => ['nullable', 'string', 'in:open,won,lost'],
            'expected_from' => ['nullable', 'date'],
            'expected_to' => ['nullable', 'date'],
            'value_min' => ['nullable', 'numeric', 'min:0'],
            'value_max' => ['nullable', 'numeric', 'min:0'],
        ]);

        return view('crm::admin.deals.index', [
            'pipeline' => $this->deals->pipeline($request),
            'deals' => $this->deals->paginate($request),
            'filters' => $this->deals->filters($request),
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
            'savedFilters' => SavedFilter::query()->forModule('deals')->visibleTo($request->user())->orderBy('name')->get(),
            'statuses' => $this->labels->dealStatuses(),
            'exportColumns' => CrmExportSchema::columns('deals'),
            'exportFormats' => CrmExportSchema::formats('deals'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.deals.create');

        return view('crm::admin.deals.form', $this->formData(new Deal));
    }

    public function store(StoreDealRequest $request, UpsertDeal $upsert): RedirectResponse
    {
        $deal = $upsert->handle(new Deal, $request->payload(), $request->user());

        return redirect()
            ->route('crm.deals.show', $deal)
            ->with('crm_status', trans('crm::messages.deals.created'));
    }

    public function show(Request $request, Deal $deal): View
    {
        Gate::authorize('view', $deal);
        $request->validate([
            'activity_type' => ['nullable', 'string', 'in:note,call,email,meeting,task_completed,quote_sent,deal_moved,system'],
        ]);

        $deal->load([
            'stage',
            'company',
            'contact',
            'owner',
            'tags',
            'tasks.assignee',
            'quotes',
        ]);

        $timeline = $deal->activities()
            ->with('user')
            ->when($request->filled('activity_type'), fn ($query) => $query->where('type', $request->string('activity_type')->toString()))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        return view('crm::admin.deals.show', [
            'deal' => $deal,
            'openTasks' => $deal->tasks->whereNull('completed_at')->sortBy('due_at'),
            'nextTask' => $deal->tasks->whereNull('completed_at')->sortBy('due_at')->first(),
            'timeline' => $timeline,
            'weightedValue' => ((float) $deal->value) * ($deal->probability / 100),
            'stages' => DealStage::query()->ordered()->get(['id', 'name', 'probability', 'is_won', 'is_lost']),
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'currencies' => array_combine(
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR']),
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR'])
            ),
            'activityTypes' => $this->labels->activityTypes(),
            'activityFilter' => $request->string('activity_type')->toString(),
            'taskPriorities' => $this->labels->taskPriorities(),
            'aiAvailable' => app(AiDriverManager::class)->available(),
            'defaultTaxRate' => app(MoneySettings::class)->defaultTaxRate(),
            'defaultTerms' => app(MoneySettings::class)->quoteTerms(),
        ]);
    }

    public function edit(Deal $deal): View
    {
        Gate::authorize('update', $deal);

        return view('crm::admin.deals.form', $this->formData($deal));
    }

    public function update(UpdateDealRequest $request, Deal $deal, UpsertDeal $upsert): RedirectResponse
    {
        $deal = $upsert->handle($deal, $request->payload(), $request->user());

        return redirect()
            ->route('crm.deals.show', $deal)
            ->with('crm_status', trans('crm::messages.deals.updated'));
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        Gate::authorize('delete', $deal);

        $deal->delete();

        return redirect()
            ->route('crm.deals.index')
            ->with('crm_status', trans('crm::messages.deals.deleted'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('crm.deals.delete');

        $validated = $request->validate([
            'record_ids' => ['required', 'array', 'min:1'],
            'record_ids.*' => ['integer', 'exists:deals,id'],
        ]);

        Deal::query()
            ->whereKey($validated['record_ids'])
            ->get()
            ->each(function (Deal $deal): void {
                Gate::authorize('delete', $deal);
                $deal->delete();
            });

        return back()->with('crm_status', trans('crm::messages.deals.bulk_deleted'));
    }

    public function move(MoveDealRequest $request, Deal $deal, MoveDealToStage $moveDeal): JsonResponse
    {
        $fromStageId = $deal->stage_id;
        $stage = DealStage::query()->findOrFail($request->validated('stage_id'));
        $deal = $moveDeal->handle(
            $deal,
            $stage,
            $request->integer('position') ?: null,
            $request->validated('lost_reason'),
            $request->user()
        );

        $affectedIds = collect([$fromStageId, $stage->id])->unique()->filter()->values();
        $aggregates = Deal::withoutTrashed()
            ->selectRaw('stage_id, COUNT(*) as deals_count, COALESCE(SUM(value), 0) as pipeline_value')
            ->whereIn('stage_id', $affectedIds)
            ->groupBy('stage_id')
            ->get()
            ->keyBy('stage_id');

        $formatter = app(CrmFormatter::class);
        $stages = $affectedIds->map(function ($stageId) use ($aggregates, $formatter) {
            $agg = $aggregates->get($stageId);
            $count = (int) ($agg?->deals_count ?? 0);
            $value = (float) ($agg?->pipeline_value ?? 0);

            return [
                'id' => $stageId,
                'deals_count' => $count,
                'count_label' => __(':count deals', ['count' => $count]),
                'value_label' => $formatter->money($value),
            ];
        })->values();

        return response()->json([
            'message' => trans('crm::messages.deals.moved'),
            'deal' => [
                'id' => $deal->id,
                'stage_id' => $deal->stage_id,
                'status' => $deal->status,
                'position' => $deal->position,
                'probability' => $deal->probability,
                'closed_at' => $deal->closed_at?->toISOString(),
                'lost_reason' => $deal->lost_reason,
            ],
            'stages' => $stages,
        ]);
    }

    public function stage(MoveDealRequest $request, Deal $deal, MoveDealToStage $moveDeal): JsonResponse|RedirectResponse
    {
        Gate::authorize('move', $deal);

        $moveDeal->handle(
            $deal,
            DealStage::query()->findOrFail($request->validated('stage_id')),
            null,
            $request->validated('lost_reason'),
            $request->user()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => trans('crm::messages.deals.stage_updated'),
                'redirect' => route('crm.deals.show', $deal),
            ]);
        }

        return redirect()
            ->route('crm.deals.show', $deal)
            ->with('crm_status', trans('crm::messages.deals.stage_updated'));
    }

    public function closeWon(Deal $deal, MoveDealToStage $moveDeal): JsonResponse|RedirectResponse
    {
        Gate::authorize('close', $deal);

        $stage = DealStage::query()->where('is_won', true)->firstOrFail();
        $moveDeal->handle($deal, $stage, null, null, request()->user());

        if (request()->expectsJson()) {
            return response()->json([
                'message' => trans('crm::messages.deals.marked_won'),
                'redirect' => route('crm.deals.show', $deal),
            ]);
        }

        return redirect()
            ->route('crm.deals.show', $deal)
            ->with('crm_status', trans('crm::messages.deals.marked_won'));
    }

    public function closeLost(CloseDealAsLostRequest $request, Deal $deal, MoveDealToStage $moveDeal): JsonResponse|RedirectResponse
    {
        Gate::authorize('close', $deal);

        $stage = DealStage::query()->where('is_lost', true)->firstOrFail();
        $moveDeal->handle($deal, $stage, null, $request->validated('lost_reason'), $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => trans('crm::messages.deals.marked_lost'),
                'redirect' => route('crm.deals.show', $deal),
            ]);
        }

        return redirect()
            ->route('crm.deals.show', $deal)
            ->with('crm_status', trans('crm::messages.deals.marked_lost'));
    }

    public function storeTask(StoreDealTaskRequest $request, Deal $deal, AddDealTask $addTask): JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $deal);

        $addTask->handle($deal, $request->validated(), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['message' => trans('crm::messages.deals.task_added')]);
        }

        return redirect()
            ->route('crm.deals.show', $deal)
            ->with('crm_status', trans('crm::messages.deals.task_added'));
    }

    public function storeQuote(StoreDealQuoteRequest $request, Deal $deal, CreateDealQuote $createQuote): RedirectResponse
    {
        Gate::authorize('view', $deal);

        $createQuote->handle($deal, $request->validated(), $request->user());

        return redirect()
            ->route('crm.deals.show', $deal)
            ->with('crm_status', trans('crm::messages.deals.quote_created'));
    }

    public function storeActivity(StoreDealActivityRequest $request, Deal $deal, AddDealActivity $addActivity): JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $deal);

        $addActivity->handle($deal, $request->validated(), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['message' => trans('crm::messages.deals.activity_added')]);
        }

        return redirect()
            ->route('crm.deals.show', $deal)
            ->with('crm_status', trans('crm::messages.deals.activity_added'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Deal $deal): array
    {
        return [
            'deal' => $deal,
            'stages' => DealStage::query()->ordered()->get(['id', 'name', 'probability', 'is_won', 'is_lost']),
            'contacts' => Contact::query()->orderBy('full_name')->limit(250)->get(['id', 'full_name']),
            'companies' => Company::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'color']),
            'selectedTags' => $deal->exists ? $deal->tags()->pluck('tags.id')->all() : [],
            'statuses' => $this->labels->dealStatuses(),
            'currencies' => array_combine(
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR']),
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR'])
            ),
            'defaultCurrency' => app(MoneySettings::class)->defaultCurrency(),
            'defaultTaxRate' => app(MoneySettings::class)->defaultTaxRate(),
            'defaultTerms' => app(MoneySettings::class)->quoteTerms(),
        ];
    }

}
