<?php

namespace App\Crm\Services\Tasks;

use App\Crm\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TaskQuery
{
    public function paginate(Request $request, ?string $scope = null): LengthAwarePaginator
    {
        return $this->baseQuery($request, $scope)
            ->orderByRaw('CASE WHEN completed_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('due_at')
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request, 25))
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request, ?string $scope = null): array
    {
        return [
            'scope' => $scope ?: 'all',
            'search' => $request->string('search')->toString(),
            'assigned_to' => $request->input('assigned_to'),
            'priority' => $request->string('priority')->toString(),
            'status' => $request->string('status')->toString(),
            'due_from' => $request->input('due_from'),
            'due_to' => $request->input('due_to'),
        ];
    }

    private function baseQuery(Request $request, ?string $scope): Builder
    {
        return Task::query()
            ->with(['assignee', 'taskable'])
            ->when($scope === 'my', fn (Builder $query) => $query->where('assigned_to', $request->user()?->id))
            ->when($scope === 'overdue', fn (Builder $query) => $query->overdue())
            ->when($scope === 'today', fn (Builder $query) => $query->dueToday())
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('assigned_to'), fn (Builder $query) => $query->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('priority'), fn (Builder $query) => $query->where('priority', $request->string('priority')->toString()))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('due_from'), fn (Builder $query) => $query->whereDate('due_at', '>=', $request->date('due_from')->toDateString()))
            ->when($request->filled('due_to'), fn (Builder $query) => $query->whereDate('due_at', '<=', $request->date('due_to')->toDateString()));
    }

    private function perPage(Request $request, int $default): int
    {
        $max = (int) config('crm.api.max_per_page', 100);

        return min(max(1, $request->integer('per_page', $default)), $max);
    }
}
