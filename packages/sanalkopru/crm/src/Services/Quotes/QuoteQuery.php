<?php

namespace Sanalkopru\Crm\Services\Quotes;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Sanalkopru\Crm\Models\Quote;

class QuoteQuery
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return $this->baseQuery($request)
            ->orderByDesc('updated_at')
            ->paginate($this->perPage($request, 25))
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'owner_id' => $request->input('owner_id'),
            'tag_id' => $request->input('tag_id'),
            'valid_from' => $request->input('valid_from'),
            'valid_to' => $request->input('valid_to'),
        ];
    }

    private function baseQuery(Request $request): Builder
    {
        return Quote::query()
            ->with(['company', 'contact', 'deal', 'owner', 'tags'])
            ->withCount('items')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('quote_number', 'like', "%{$term}%")
                        ->orWhereHas('company', fn (Builder $query) => $query->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('contact', fn (Builder $query) => $query->where('full_name', 'like', "%{$term}%"))
                        ->orWhereHas('deal', fn (Builder $query) => $query->where('title', 'like', "%{$term}%"));
                });
            })
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('owner_id'), fn (Builder $query) => $query->where('owner_id', $request->integer('owner_id')))
            ->when($request->filled('tag_id'), fn (Builder $query) => $query->whereHas('tags', fn (Builder $query) => $query->whereKey($request->integer('tag_id'))))
            ->when($request->filled('valid_from'), fn (Builder $query) => $query->whereDate('valid_until', '>=', $request->date('valid_from')->toDateString()))
            ->when($request->filled('valid_to'), fn (Builder $query) => $query->whereDate('valid_until', '<=', $request->date('valid_to')->toDateString()));
    }

    private function perPage(Request $request, int $default): int
    {
        $max = (int) config('crm.api.max_per_page', 100);

        return min(max(1, $request->integer('per_page', $default)), $max);
    }
}
