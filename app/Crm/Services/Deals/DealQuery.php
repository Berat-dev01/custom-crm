<?php

namespace App\Crm\Services\Deals;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;

class DealQuery
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return $this->baseQuery($request)
            ->orderByDesc('updated_at')
            ->paginate($this->perPage($request, 25))
            ->withQueryString();
    }

    /**
     * @return Collection<int, DealStage>
     */
    public function pipeline(Request $request): Collection
    {
        $perStageLimit = $this->kanbanPerStageLimit($request);
        $stages = DealStage::query()->ordered()->get();
        $deals = $stages->mapWithKeys(function (DealStage $stage) use ($request, $perStageLimit): array {
            $stageDeals = $this->baseQuery($request)
                ->where('stage_id', $stage->id)
                ->orderBy('position')
                ->orderBy('id')
                ->limit($perStageLimit)
                ->get();

            return [$stage->id => $stageDeals];
        });
        $counts = $this->applyFilters(
            Deal::query()->selectRaw('stage_id, COUNT(*) as deals_count, COALESCE(SUM(value), 0) as pipeline_value'),
            $request
        )
            ->groupBy('stage_id')
            ->get()
            ->keyBy('stage_id');

        return $stages
            ->map(function (DealStage $stage) use ($counts, $deals, $perStageLimit): DealStage {
                $aggregate = $counts->get($stage->id);
                $dealsCount = (int) ($aggregate?->deals_count ?? 0);

                $stage->setRelation('deals', $deals->get($stage->id, collect()));
                $stage->setAttribute('deals_count', $dealsCount);
                $stage->setAttribute('pipeline_value', (float) ($aggregate?->pipeline_value ?? 0));
                $stage->setAttribute('kanban_limit', $perStageLimit);
                $stage->setAttribute('has_more_deals', $dealsCount > $stage->deals->count());

                return $stage;
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        return [
            'view' => $request->string('view')->toString() ?: 'kanban',
            'search' => $request->string('search')->toString(),
            'owner_id' => $request->input('owner_id'),
            'tag_id' => $request->input('tag_id'),
            'status' => $request->string('status')->toString(),
            'expected_from' => $request->input('expected_from'),
            'expected_to' => $request->input('expected_to'),
            'value_min' => $request->input('value_min'),
            'value_max' => $request->input('value_max'),
        ];
    }

    private function baseQuery(Request $request): Builder
    {
        return $this->applyFilters(
            Deal::query()
                ->with(['stage', 'company', 'contact', 'owner', 'tags'])
                ->withCount([
                    'tasks as open_tasks_count' => fn (Builder $query) => $query->whereNull('completed_at'),
                ]),
            $request
        );
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhereHas('company', fn (Builder $query) => $query->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('contact', fn (Builder $query) => $query->where('full_name', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('owner_id'), fn (Builder $query) => $query->where('owner_id', $request->integer('owner_id')))
            ->when($request->filled('tag_id'), fn (Builder $query) => $query->whereHas('tags', fn (Builder $query) => $query->whereKey($request->integer('tag_id'))))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('expected_from'), fn (Builder $query) => $query->whereDate('expected_close_date', '>=', $request->date('expected_from')->toDateString()))
            ->when($request->filled('expected_to'), fn (Builder $query) => $query->whereDate('expected_close_date', '<=', $request->date('expected_to')->toDateString()))
            ->when($request->filled('value_min'), fn (Builder $query) => $query->where('value', '>=', $request->input('value_min')))
            ->when($request->filled('value_max'), fn (Builder $query) => $query->where('value', '<=', $request->input('value_max')));
    }

    private function perPage(Request $request, int $default): int
    {
        $max = (int) config('crm.api.max_per_page', 100);

        return min(max(1, $request->integer('per_page', $default)), $max);
    }

    private function kanbanPerStageLimit(Request $request): int
    {
        $default = (int) config('crm.performance.kanban_per_stage_limit', 50);
        $max = (int) config('crm.performance.kanban_per_stage_max_limit', 100);

        return min(max(1, $request->integer('per_stage', $default)), $max);
    }
}
