<?php

namespace Sanalkopru\Crm\Services\Activities;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;

class ActivityQuery
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return $this->baseQuery($request)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
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
            'type' => $request->string('type')->toString(),
            'activityable_type' => $request->string('activityable_type')->toString(),
            'user_id' => $request->input('user_id'),
            'occurred_from' => $request->input('occurred_from'),
            'occurred_to' => $request->input('occurred_to'),
        ];
    }

    private function baseQuery(Request $request): Builder
    {
        return Activity::query()
            ->with(['user', 'activityable'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $term = $request->string('search')->toString();

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('subject', 'like', "%{$term}%")
                        ->orWhere('body', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('type'), fn (Builder $query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('activityable_type'), fn (Builder $query) => $query->where('activityable_type', $this->activityableClass($request->string('activityable_type')->toString())))
            ->when($request->filled('user_id'), fn (Builder $query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('occurred_from'), fn (Builder $query) => $query->whereDate('occurred_at', '>=', $request->date('occurred_from')->toDateString()))
            ->when($request->filled('occurred_to'), fn (Builder $query) => $query->whereDate('occurred_at', '<=', $request->date('occurred_to')->toDateString()));
    }

    public function activityableClass(string $type): ?string
    {
        return match ($type) {
            'contact' => Contact::class,
            'company' => Company::class,
            'deal' => Deal::class,
            'quote' => Quote::class,
            default => null,
        };
    }

    private function perPage(Request $request, int $default): int
    {
        $max = (int) config('crm.api.max_per_page', 100);

        return min(max(1, $request->integer('per_page', $default)), $max);
    }
}
