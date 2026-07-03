<?php

namespace App\Crm\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use App\Crm\Actions\Tasks\CompleteTask;
use App\Crm\Actions\Tasks\UpsertTask;
use App\Crm\Http\Requests\Tasks\StoreTaskRequest;
use App\Crm\Http\Requests\Tasks\UpdateTaskRequest;
use App\Crm\Http\Resources\Api\TaskResource;
use App\Crm\Models\Task;
use App\Crm\Services\Tasks\TaskQuery;

class TasksController extends Controller
{
    public function __construct(private readonly TaskQuery $tasks) {}

    public function index(Request $request): mixed
    {
        Gate::authorize('viewAny', Task::class);
        $this->validateIndex($request);

        $scope = $request->string('scope')->toString() ?: null;

        return TaskResource::collection($this->tasks->paginate($request, $scope));
    }

    public function store(StoreTaskRequest $request, UpsertTask $upsert): mixed
    {
        $task = $upsert->handle(new Task, $request->payload(), $request->user());

        return (new TaskResource($task->load(['assignee', 'taskable'])))
            ->additional(['message' => trans('crm::messages.tasks.created')])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task->load(['assignee', 'taskable']));
    }

    public function update(UpdateTaskRequest $request, Task $task, UpsertTask $upsert): TaskResource
    {
        Gate::authorize('update', $task);

        $task = $upsert->handle($task, $request->payload(), $request->user());

        return (new TaskResource($task->load(['assignee', 'taskable'])))
            ->additional(['message' => trans('crm::messages.tasks.updated')]);
    }

    public function complete(Task $task, CompleteTask $completeTask): TaskResource
    {
        Gate::authorize('complete', $task);

        $task = $completeTask->handle($task, request()->user());

        return (new TaskResource($task->load(['assignee', 'taskable'])))
            ->additional(['message' => trans('crm::messages.tasks.completed')]);
    }

    public function destroy(Task $task): mixed
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->json(['message' => trans('crm::messages.tasks.deleted')]);
    }

    private function validateIndex(Request $request): void
    {
        $request->validate([
            'scope' => ['nullable', 'string', 'in:all,my,today,overdue'],
            'search' => ['nullable', 'string', 'max:120'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'status' => ['nullable', 'string', 'in:open,in_progress,completed,cancelled'],
            'due_from' => ['nullable', 'date'],
            'due_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('crm.api.max_per_page', 100)],
        ]);
    }
}
