<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Http\Requests\Activities\StoreActivityRequest;
use Sanalkopru\Crm\Http\Requests\Activities\UpdateActivityRequest;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\SavedFilter;
use Sanalkopru\Crm\Services\Activities\ActivityLogger;
use Sanalkopru\Crm\Services\Activities\ActivityQuery;
use Sanalkopru\Crm\Support\CrmLabelCatalog;

class ActivitiesController extends Controller
{
    public function __construct(
        private readonly ActivityQuery $activities,
        private readonly CrmLabelCatalog $labels
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('crm.activities.view');

        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'in:note,call,email,meeting,task_completed,quote_sent,deal_moved,system'],
            'activityable_type' => ['nullable', 'string', 'in:contact,company,deal,quote'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'occurred_from' => ['nullable', 'date'],
            'occurred_to' => ['nullable', 'date'],
        ]);

        return view('crm::admin.activities.index', [
            'activities' => $this->activities->paginate($request),
            'filters' => $this->activities->filters($request),
            'types' => $this->labels->activityTypes(),
            'activityableTypes' => $this->labels->relatedRecordTypes(),
            'users' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'savedFilters' => SavedFilter::query()->forModule('activities')->visibleTo($request->user())->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('crm.activities.create');

        return view('crm::admin.activities.form', $this->formData(new Activity));
    }

    public function store(StoreActivityRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->activityData();
        $activity = $logger->manual($data['activityable'], $data['payload'], $request->user());

        return redirect()
            ->route('crm.activities.show', $activity)
            ->with('crm_status', 'Activity created.');
    }

    public function show(Activity $activity): View
    {
        Gate::authorize('view', $activity);

        $activity->load(['user', 'activityable']);

        return view('crm::admin.activities.show', [
            'activity' => $activity,
        ]);
    }

    public function edit(Activity $activity): View
    {
        Gate::authorize('update', $activity);

        return view('crm::admin.activities.form', $this->formData($activity));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $payload = $request->validated();
        $payload['updated_by'] = $request->user()?->getAuthIdentifier();
        $payload['subject'] = trim(strip_tags($payload['subject']));
        $payload['body'] = isset($payload['body']) ? trim(strip_tags($payload['body'])) : null;

        $activity->fill($payload);
        $activity->save();

        return redirect()
            ->route('crm.activities.show', $activity)
            ->with('crm_status', 'Activity updated.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        Gate::authorize('delete', $activity);

        $activity->delete();

        return redirect()
            ->route('crm.activities.index')
            ->with('crm_status', 'Activity deleted.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('crm.activities.delete');

        $validated = $request->validate([
            'record_ids' => ['required', 'array', 'min:1'],
            'record_ids.*' => ['integer', 'exists:activities,id'],
        ]);

        Activity::query()
            ->whereKey($validated['record_ids'])
            ->get()
            ->each(function (Activity $activity): void {
                Gate::authorize('delete', $activity);
                $activity->delete();
            });

        return back()->with('crm_status', 'Selected activities deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Activity $activity): array
    {
        return [
            'activity' => $activity,
            'types' => array_intersect_key($this->labels->activityTypes(), array_flip(['note', 'call', 'email', 'meeting'])),
            'activityableTypes' => $this->labels->relatedRecordTypes(),
            'activityableOptions' => [
                'contact' => Contact::query()->orderBy('full_name')->limit(250)->get(['id', 'full_name']),
                'company' => Company::query()->orderBy('name')->limit(250)->get(['id', 'name']),
                'deal' => Deal::query()->orderBy('title')->limit(250)->get(['id', 'title']),
                'quote' => Quote::query()->orderByDesc('created_at')->limit(250)->get(['id', 'quote_number']),
            ],
            'selectedActivityableType' => $this->selectedActivityableType($activity),
        ];
    }

    private function selectedActivityableType(Activity $activity): ?string
    {
        return match ($activity->activityable_type) {
            Contact::class => 'contact',
            Company::class => 'company',
            Deal::class => 'deal',
            Quote::class => 'quote',
            default => null,
        };
    }
}
