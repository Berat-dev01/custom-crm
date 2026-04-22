<?php

namespace Sanalkopru\Crm\Services\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Models\Task;

class DashboardReport
{
    /**
     * @return array<string, mixed>
     */
    public function build(Request $request, Authenticatable $user): array
    {
        $filters = $this->filters($request);
        $range = $this->dateRange($filters);
        $canViewAll = $this->canViewAll($user);

        return [
            'filters' => $filters,
            'range' => $range,
            'canViewAll' => $canViewAll,
            'stats' => $this->stats($user, $canViewAll, $range),
            'pipelineByStage' => $this->pipelineByStage($user, $canViewAll),
            'monthlyTrend' => $this->monthlyTrend($user, $canViewAll, $range['end']),
            'upcomingTasks' => $this->upcomingTasks($user, $canViewAll),
            'recentActivities' => $this->recentActivities($user, $canViewAll, $range),
            'topOpenDeals' => $this->topOpenDeals($user, $canViewAll),
            'quoteStatusDistribution' => $this->quoteStatusDistribution($user, $canViewAll, $range),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function filters(Request $request): array
    {
        $period = $request->string('period')->toString() ?: 'this_month';

        if (! in_array($period, ['today', 'this_week', 'this_month', 'custom'], true)) {
            $period = 'this_month';
        }

        return [
            'period' => $period,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];
    }

    /**
     * @param  array<string, string|null>  $filters
     * @return array{start: CarbonImmutable, end: CarbonImmutable, label: string}
     */
    public function dateRange(array $filters): array
    {
        $now = CarbonImmutable::now();

        if ($filters['period'] === 'today') {
            $start = $now->startOfDay();
            $end = $now->endOfDay();
        } elseif ($filters['period'] === 'this_week') {
            $start = $now->startOfWeek();
            $end = $now->endOfWeek();
        } elseif ($filters['period'] === 'custom') {
            $start = $filters['date_from']
                ? CarbonImmutable::parse($filters['date_from'])->startOfDay()
                : $now->startOfMonth();
            $end = $filters['date_to']
                ? CarbonImmutable::parse($filters['date_to'])->endOfDay()
                : $now->endOfDay();
        } else {
            $start = $now->startOfMonth();
            $end = $now->endOfMonth();
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => $start->format('Y-m-d').' - '.$end->format('Y-m-d'),
        ];
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, label: string}  $range
     * @return array<string, mixed>
     */
    private function stats(Authenticatable $user, bool $canViewAll, array $range): array
    {
        $openDeals = $this->scopeOwner(Deal::query()->where('status', 'open'), $user, $canViewAll);

        return [
            'contacts' => $this->scopeOwner(Contact::query(), $user, $canViewAll)->count(),
            'companies' => $this->scopeOwner(Company::query(), $user, $canViewAll)->count(),
            'open_deals' => (clone $openDeals)->count(),
            'open_pipeline_value' => (float) (clone $openDeals)->sum('value'),
            'weighted_pipeline_value' => (float) (clone $openDeals)
                ->selectRaw('COALESCE(SUM(value * probability / 100), 0) as aggregate')
                ->value('aggregate'),
            'won_deal_value' => (float) $this->withinDateRange(
                $this->scopeOwner(Deal::query()->where('status', 'won'), $user, $canViewAll),
                'closed_at',
                $range
            )->sum('value'),
            'overdue_tasks' => $this->scopeTasks(Task::query()->incomplete()->whereNotNull('due_at')->where('due_at', '<', now()), $user, $canViewAll)->count(),
            'sent_quotes' => $this->withinDateRange(
                $this->scopeOwner(Quote::query()->where('status', 'sent'), $user, $canViewAll),
                'created_at',
                $range
            )->count(),
            'accepted_quotes' => $this->withinDateRange(
                $this->scopeOwner(Quote::query()->where('status', 'accepted'), $user, $canViewAll),
                'created_at',
                $range
            )->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function pipelineByStage(Authenticatable $user, bool $canViewAll): Collection
    {
        $aggregates = $this->scopeOwner(Deal::query()->where('status', 'open'), $user, $canViewAll)
            ->selectRaw('stage_id, COUNT(*) as deals_count, COALESCE(SUM(value), 0) as pipeline_value')
            ->groupBy('stage_id')
            ->get()
            ->keyBy('stage_id');

        return DealStage::query()
            ->ordered()
            ->get(['id', 'name', 'color'])
            ->map(fn (DealStage $stage): array => [
                'id' => $stage->id,
                'name' => $stage->name,
                'color' => $stage->color,
                'deals_count' => (int) ($aggregates->get($stage->id)?->deals_count ?? 0),
                'pipeline_value' => (float) ($aggregates->get($stage->id)?->pipeline_value ?? 0),
            ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function monthlyTrend(Authenticatable $user, bool $canViewAll, CarbonImmutable $end): array
    {
        return collect(range(5, 0))
            ->map(function (int $monthsBack) use ($user, $canViewAll, $end): array {
                $month = $end->subMonths($monthsBack);
                $start = $month->startOfMonth();
                $finish = $month->endOfMonth();
                $won = $this->scopeOwner(Deal::query()->where('status', 'won'), $user, $canViewAll)
                    ->whereBetween('closed_at', [$start, $finish]);
                $lost = $this->scopeOwner(Deal::query()->where('status', 'lost'), $user, $canViewAll)
                    ->whereBetween('closed_at', [$start, $finish]);

                return [
                    'label' => $month->format('M Y'),
                    'won_count' => (clone $won)->count(),
                    'won_value' => (float) (clone $won)->sum('value'),
                    'lost_count' => (clone $lost)->count(),
                ];
            })
            ->all();
    }

    /**
     * @return Collection<int, Task>
     */
    private function upcomingTasks(Authenticatable $user, bool $canViewAll): Collection
    {
        return $this->scopeTasks(Task::query()->incomplete()->whereNotNull('due_at'), $user, $canViewAll)
            ->where('due_at', '>=', now())
            ->with(['assignee', 'taskable'])
            ->orderBy('due_at')
            ->limit(6)
            ->get();
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, label: string}  $range
     * @return Collection<int, Activity>
     */
    private function recentActivities(Authenticatable $user, bool $canViewAll, array $range): Collection
    {
        return $this->withinDateRange($this->scopeActivities(Activity::query(), $user, $canViewAll), 'occurred_at', $range)
            ->with(['user', 'activityable'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Deal>
     */
    private function topOpenDeals(Authenticatable $user, bool $canViewAll): Collection
    {
        return $this->scopeOwner(Deal::query()->where('status', 'open'), $user, $canViewAll)
            ->with(['company', 'contact', 'stage', 'owner'])
            ->orderByDesc('value')
            ->limit(6)
            ->get();
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, label: string}  $range
     * @return Collection<int, array<string, mixed>>
     */
    private function quoteStatusDistribution(Authenticatable $user, bool $canViewAll, array $range): Collection
    {
        $counts = $this->withinDateRange($this->scopeOwner(Quote::query(), $user, $canViewAll), 'created_at', $range)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(['draft', 'sent', 'accepted', 'rejected', 'expired'])
            ->map(fn (string $status): array => [
                'status' => $status,
                'label' => ucfirst($status),
                'total' => (int) ($counts[$status] ?? 0),
            ]);
    }

    private function canViewAll(Authenticatable $user): bool
    {
        return method_exists($user, 'can') && $user->can('crm.reports.view');
    }

    private function scopeOwner(Builder $query, Authenticatable $user, bool $canViewAll): Builder
    {
        return $canViewAll ? $query : $query->where('owner_id', $user->getAuthIdentifier());
    }

    private function scopeTasks(Builder $query, Authenticatable $user, bool $canViewAll): Builder
    {
        return $canViewAll ? $query : $query->where('assigned_to', $user->getAuthIdentifier());
    }

    private function scopeActivities(Builder $query, Authenticatable $user, bool $canViewAll): Builder
    {
        return $canViewAll ? $query : $query->where('user_id', $user->getAuthIdentifier());
    }

    /**
     * @param  array{start: CarbonImmutable, end: CarbonImmutable, label: string}  $range
     */
    private function withinDateRange(Builder $query, string $column, array $range): Builder
    {
        return $query->whereBetween($column, [$range['start'], $range['end']]);
    }
}
