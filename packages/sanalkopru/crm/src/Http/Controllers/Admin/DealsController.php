<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Actions\Deals\MoveDealToStage;
use Sanalkopru\Crm\Actions\Deals\UpsertDeal;
use Sanalkopru\Crm\Http\Requests\Deals\MoveDealRequest;
use Sanalkopru\Crm\Http\Requests\Deals\StoreDealRequest;
use Sanalkopru\Crm\Http\Requests\Deals\UpdateDealRequest;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Tag;
use Sanalkopru\Crm\Services\Deals\DealQuery;

class DealsController extends Controller
{
    public function __construct(private readonly DealQuery $deals) {}

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
            'statuses' => $this->statuses(),
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
            ->with('crm_status', 'Deal created.');
    }

    public function show(Deal $deal): View
    {
        Gate::authorize('view', $deal);

        $deal->load([
            'stage',
            'company',
            'contact',
            'owner',
            'tags',
            'tasks.assignee',
            'quotes',
            'activities.user',
        ]);

        return view('crm::admin.deals.show', [
            'deal' => $deal,
            'openTasks' => $deal->tasks->whereNull('completed_at')->sortBy('due_at'),
            'timeline' => $deal->activities->sortByDesc('occurred_at'),
            'weightedValue' => ((float) $deal->value) * ($deal->probability / 100),
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
            ->with('crm_status', 'Deal updated.');
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        Gate::authorize('delete', $deal);

        $deal->delete();

        return redirect()
            ->route('crm.deals.index')
            ->with('crm_status', 'Deal deleted.');
    }

    public function move(MoveDealRequest $request, Deal $deal, MoveDealToStage $moveDeal): JsonResponse
    {
        $stage = DealStage::query()->findOrFail($request->validated('stage_id'));
        $deal = $moveDeal->handle(
            $deal,
            $stage,
            $request->integer('position') ?: null,
            $request->validated('lost_reason'),
            $request->user()
        );

        return response()->json([
            'message' => 'Deal moved.',
            'deal' => [
                'id' => $deal->id,
                'stage_id' => $deal->stage_id,
                'status' => $deal->status,
                'position' => $deal->position,
                'probability' => $deal->probability,
                'closed_at' => $deal->closed_at?->toISOString(),
                'lost_reason' => $deal->lost_reason,
            ],
        ]);
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
            'statuses' => $this->statuses(),
            'currencies' => array_combine(
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR']),
                config('crm.money.supported_currencies', ['TRY', 'USD', 'EUR'])
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function statuses(): array
    {
        return [
            'open' => 'Open',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
    }
}
