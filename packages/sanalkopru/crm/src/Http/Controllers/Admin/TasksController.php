<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Actions\Tasks\CompleteTask;
use Sanalkopru\Crm\Actions\Tasks\UpsertTask;
use Sanalkopru\Crm\Http\Requests\Tasks\StoreTaskRequest;
use Sanalkopru\Crm\Http\Requests\Tasks\UpdateTaskRequest;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\SavedFilter;
use Sanalkopru\Crm\Models\Task;
use Sanalkopru\Crm\Services\Tasks\TaskQuery;
use Sanalkopru\Crm\Support\CrmLabelCatalog;

class TasksController extends Controller
{
    public function __construct(
        private readonly TaskQuery $tasks,
        private readonly CrmLabelCatalog $labels
    ) {}

    public function index(Request $request): View
    {
        return $this->taskIndex($request);
    }

    public function my(Request $request): View
    {
        return $this->taskIndex($request, 'my');
    }

    public function overdue(Request $request): View
    {
        return $this->taskIndex($request, 'overdue');
    }

    public function today(Request $request): View
    {
        return $this->taskIndex($request, 'today');
    }

    public function create(): View
    {
        Gate::authorize('crm.tasks.create');

        return view('crm::admin.tasks.form', $this->formData(new Task));
    }

    public function store(StoreTaskRequest $request, UpsertTask $upsert): RedirectResponse
    {
        $task = $upsert->handle(new Task, $request->payload(), $request->user());

        return redirect()
            ->route('crm.tasks.show', $task)
            ->with('crm_status', 'Task created.');
    }

    public function show(Task $task): View
    {
        Gate::authorize('view', $task);

        $task->load(['assignee', 'taskable']);

        return view('crm::admin.tasks.show', [
            'task' => $task,
        ]);
    }

    public function edit(Task $task): View
    {
        Gate::authorize('update', $task);

        return view('crm::admin.tasks.form', $this->formData($task));
    }

    public function update(UpdateTaskRequest $request, Task $task, UpsertTask $upsert): RedirectResponse
    {
        $upsert->handle($task, $request->payload(), $request->user());

        return redirect()
            ->route('crm.tasks.show', $task)
            ->with('crm_status', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return redirect()
            ->route('crm.tasks.index')
            ->with('crm_status', 'Task deleted.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('crm.tasks.delete');

        $validated = $request->validate([
            'record_ids' => ['required', 'array', 'min:1'],
            'record_ids.*' => ['integer', 'exists:tasks,id'],
        ]);

        Task::query()
            ->whereKey($validated['record_ids'])
            ->get()
            ->each(function (Task $task): void {
                Gate::authorize('delete', $task);
                $task->delete();
            });

        return back()->with('crm_status', 'Selected tasks deleted.');
    }

    public function complete(Task $task, CompleteTask $completeTask): JsonResponse|RedirectResponse
    {
        Gate::authorize('complete', $task);

        $completeTask->handle($task, request()->user());

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Task completed.']);
        }

        return back()->with('crm_status', 'Task completed.');
    }

    private function taskIndex(Request $request, ?string $scope = null): View
    {
        Gate::authorize('crm.tasks.view');

        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'status' => ['nullable', 'string', 'in:open,in_progress,completed,cancelled'],
            'due_from' => ['nullable', 'date'],
            'due_to' => ['nullable', 'date'],
        ]);

        return view('crm::admin.tasks.index', [
            'tasks' => $this->tasks->paginate($request, $scope),
            'filters' => $this->tasks->filters($request, $scope),
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'priorities' => $this->labels->taskPriorities(),
            'statuses' => $this->labels->taskStatuses(),
            'savedFilters' => SavedFilter::query()->forModule('tasks')->visibleTo($request->user())->orderBy('name')->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Task $task): array
    {
        return [
            'task' => $task,
            'owners' => User::query()->orderBy('name')->limit(250)->get(['id', 'name']),
            'priorities' => $this->labels->taskPriorities(),
            'statuses' => $this->labels->taskStatuses(),
            'taskableTypes' => $this->labels->relatedRecordTypes(),
            'taskableOptions' => [
                'contact' => Contact::query()->orderBy('full_name')->limit(250)->get(['id', 'full_name']),
                'company' => Company::query()->orderBy('name')->limit(250)->get(['id', 'name']),
                'deal' => Deal::query()->orderBy('title')->limit(250)->get(['id', 'title']),
                'quote' => Quote::query()->orderByDesc('created_at')->limit(250)->get(['id', 'quote_number']),
            ],
            'selectedTaskableType' => $this->selectedTaskableType($task),
        ];
    }

    private function selectedTaskableType(Task $task): ?string
    {
        return match ($task->taskable_type) {
            Contact::class => 'contact',
            Company::class => 'company',
            Deal::class => 'deal',
            Quote::class => 'quote',
            default => null,
        };
    }
}
