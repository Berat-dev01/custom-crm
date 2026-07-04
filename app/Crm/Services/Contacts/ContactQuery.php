<?php

namespace App\Crm\Services\Contacts;

use App\Crm\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ContactQuery
{
    public const SORTS = [
        'full_name',
        'email',
        'phone',
        'lifecycle_stage',
        'source',
        'last_contacted_at',
        'created_at',
    ];

    public function paginate(Request $request): LengthAwarePaginator
    {
        return $this->base($request)
            ->paginate($this->perPage($request))
            ->withQueryString();
    }

    /**
     * @return Collection<int, Contact>
     */
    public function forExport(Request $request): Collection
    {
        return $this->base($request)
            ->limit(5000)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'lifecycle_stage' => $request->string('lifecycle_stage')->toString(),
            'source' => $request->string('source')->toString(),
            'company_id' => $request->integer('company_id') ?: null,
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

        return Contact::query()
            ->with(['company:id,name', 'owner:id,name', 'tags:id,name,color'])
            ->withCount(['deals', 'tasks', 'quotes'])
            ->search($filters['search'])
            ->when($filters['lifecycle_stage'], fn (Builder $query, string $stage) => $query->where('lifecycle_stage', $stage))
            ->when($filters['source'], fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['company_id'], fn (Builder $query, int $companyId) => $query->where('company_id', $companyId))
            ->when($filters['owner_id'], fn (Builder $query, int $ownerId) => $query->where('owner_id', $ownerId))
            ->when($filters['tag_id'], fn (Builder $query, int $tagId) => $query->whereHas('tags', fn (Builder $tagQuery) => $tagQuery->whereKey($tagId)))
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc');
    }

    private function perPage(Request $request): int
    {
        $default = (int) config('crm.api.default_per_page', 20);
        $max = (int) config('crm.api.max_per_page', 100);

        return min(max(1, $request->integer('per_page', $default)), $max);
    }
}
