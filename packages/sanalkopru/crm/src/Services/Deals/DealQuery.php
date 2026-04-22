<?php

namespace Sanalkopru\Crm\Services\Deals;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;

class DealQuery
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return $this->baseQuery($request)
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @return Collection<int, DealStage>
     */
    public function pipeline(Request $request): Collection
    {
        $deals = $this->baseQuery($request)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('stage_id');

        return DealStage::query()
            ->withCount(['deals' => fn (Builder $query) => $this->applyFilters($query, $request)])
            ->ordered()
            ->get()
            ->map(function (DealStage $stage) use ($deals): DealStage {
                $stage->setRelation('deals', $deals->get($stage->id, collect()));
                $stage->setAttribute('pipeline_value', $stage->deals->sum(fn (Deal $deal): float => (float) $deal->value));

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
}
