<?php

namespace Sanalkopru\Crm\Services\Companies;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Sanalkopru\Crm\Models\Company;

class CompanyQuery
{
    public const SORTS = [
        'name',
        'email',
        'phone',
        'sector',
        'city',
        'created_at',
    ];

    public function paginate(Request $request): LengthAwarePaginator
    {
        return $this->base($request)
            ->paginate((int) $request->integer('per_page', 20))
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'sector' => $request->string('sector')->toString(),
            'city' => $request->string('city')->toString(),
            'owner_id' => $request->integer('owner_id') ?: null,
            'tag_id' => $request->integer('tag_id') ?: null,
            'sort' => $request->string('sort', 'created_at')->toString(),
            'direction' => $request->string('direction', 'desc')->toString(),
        ];
    }

    private function base(Request $request): Builder
    {
        $filters = $this->filters($request);
        $sort = in_array($filters['sort'], self::SORTS, true) ? $filters['sort'] : 'created_at';
        $direction = $filters['direction'] === 'asc' ? 'asc' : 'desc';

        return Company::query()
            ->with(['owner:id,name', 'tags:id,name,color'])
            ->withCount(['contacts', 'deals', 'quotes'])
            ->search($filters['search'])
            ->when($filters['sector'], fn (Builder $query, string $sector) => $query->where('sector', $sector))
            ->when($filters['city'], fn (Builder $query, string $city) => $query->where('city', 'like', "%{$city}%"))
            ->when($filters['owner_id'], fn (Builder $query, int $ownerId) => $query->where('owner_id', $ownerId))
            ->when($filters['tag_id'], fn (Builder $query, int $tagId) => $query->whereHas('tags', fn (Builder $tagQuery) => $tagQuery->whereKey($tagId)))
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc');
    }
}
