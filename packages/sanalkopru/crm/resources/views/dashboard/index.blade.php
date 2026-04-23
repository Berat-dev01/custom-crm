@extends('admin-panel::layouts.app')

@section('title', 'CRM Dashboard')
@section('page-title', 'CRM Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $money = fn (float|int|string|null $value, ?string $currency = null): string => $crmFormat->money($value, $currency);
        $maxPipeline = max(1, $pipelineByStage->max('pipeline_value') ?: 1);
        $maxQuoteStatus = max(1, $quoteStatusDistribution->max('total') ?: 1);
        $maxTrend = max(1, collect($monthlyTrend)->max(fn($row) => max($row['won_count'], $row['lost_count'])) ?: 1);
        $periodOptions = [
            'today' => 'Today',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'custom' => 'Custom',
        ];
    @endphp

    <section class="crm-admin-page" data-crm-module="dashboard">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM Engine</p>
                <h1>Sales Dashboard</h1>
                <p class="crm-muted">
                    {{ $canViewAll ? 'All team records' : 'Your owned and assigned records' }} / {{ $range['label'] }}.
                    Period filters affect won value, quote activity, recent activity, and trend charts. Snapshot metrics stay current.
                </p>
            </div>
        </header>

        <x-admin-panel::card>
            <form method="GET" action="{{ route('crm.dashboard') }}" class="crm-filter-grid">
                <x-admin-panel::select name="period" label="Period" :options="$periodOptions" :selected="$filters['period']" />
                <x-admin-panel::input name="date_from" label="From" type="date" :value="$filters['date_from']" />
                <x-admin-panel::input name="date_to" label="To" type="date" :value="$filters['date_to']" />
                <div class="crm-filter-actions">
                    <x-admin-panel::button type="submit" icon="filter">Apply</x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.dashboard')" variant="ghost">Reset</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        <div class="crm-dashboard-section-label">Current snapshot</div>

        <div class="crm-admin-grid">
            <x-admin-panel::stat-card label="Contacts" :value="number_format($stats['contacts'])" icon="users" variant="primary" />
            <x-admin-panel::stat-card label="Companies" :value="number_format($stats['companies'])" icon="building-2" variant="info" />
            <x-admin-panel::stat-card label="Open Deals" :value="number_format($stats['open_deals'])" icon="briefcase" variant="primary" />
            <x-admin-panel::stat-card label="Open Pipeline" :value="$money($stats['open_pipeline_value'])" icon="chart-no-axes-column" variant="success" />
            <x-admin-panel::stat-card label="Weighted Pipeline" :value="$money($stats['weighted_pipeline_value'])" icon="percent" variant="info" />
            <x-admin-panel::stat-card label="Overdue Tasks" :value="number_format($stats['overdue_tasks'])" icon="alarm-clock" variant="{{ $stats['overdue_tasks'] > 0 ? 'danger' : 'success' }}" />
        </div>

        <div class="crm-dashboard-section-label">Period activity</div>

        <div class="crm-admin-grid">
            <x-admin-panel::stat-card label="Won Value" :value="$money($stats['won_deal_value'])" icon="trophy" variant="success" />
            <x-admin-panel::stat-card label="Quotes Sent / Accepted" :value="number_format($stats['sent_quotes']).' / '.number_format($stats['accepted_quotes'])" icon="file-text" variant="info" />
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Pipeline by Stage
                </x-slot:header>

                <div class="crm-stack">
                    @foreach($pipelineByStage as $stage)
                        <div class="crm-dashboard-row">
                            <div class="crm-dashboard-row-head">
                                <strong>
                                    <span class="crm-color-swatch" style="background: {{ $stage['color'] }}"></span>
                                    {{ $stage['name'] }}
                                </strong>
                                <span>{{ $stage['deals_count'] }} deals / {{ $money($stage['pipeline_value']) }}</span>
                            </div>
                            <div class="crm-dashboard-bar">
                                <span style="width: {{ min(100, round(($stage['pipeline_value'] / $maxPipeline) * 100)) }}%"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    Monthly Won/Lost Trend
                </x-slot:header>

                <div class="crm-stack">
                    @foreach($monthlyTrend as $month)
                        <div class="crm-dashboard-row">
                            <div class="crm-dashboard-row-head">
                                <strong>{{ $month['label'] }}</strong>
                                <span>{{ $month['won_count'] }} won / {{ $month['lost_count'] }} lost</span>
                            </div>
                            <div class="crm-dashboard-split-bars">
                                <span class="is-won" style="width: {{ min(100, round(($month['won_count'] / $maxTrend) * 100)) }}%"></span>
                                <span class="is-lost" style="width: {{ min(100, round(($month['lost_count'] / $maxTrend) * 100)) }}%"></span>
                            </div>
                            <div class="crm-muted">{{ $money($month['won_value']) }} won value</div>
                        </div>
                    @endforeach
                </div>
            </x-admin-panel::card>
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Upcoming Tasks
                </x-slot:header>

                <div class="crm-stack">
                    @forelse($upcomingTasks as $task)
                        <div class="crm-list-item">
                            <strong>{{ $task->title }}</strong>
                            <span>{{ $crmFormat->datetime($task->due_at) }} / {{ $crmFormat->status($task->priority) }} / {{ $task->assignee?->name ?: 'Unassigned' }}</span>
                        </div>
                    @empty
                        <p class="crm-muted">No upcoming tasks.</p>
                    @endforelse
                </div>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    Recent Activities
                </x-slot:header>

                <div class="crm-stack">
                    @forelse($recentActivities as $activity)
                        <div class="crm-timeline-item">
                            <strong>{{ $activity->subject }}</strong>
                            <span>{{ $crmFormat->status($activity->type) }} / {{ $crmFormat->datetime($activity->occurred_at) }} / {{ $activity->user?->name ?: 'System' }}</span>
                            @if($activity->body)
                                <p>{{ $activity->body }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="crm-muted">No activity in this period.</p>
                    @endforelse
                </div>
            </x-admin-panel::card>
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Highest Value Open Deals
                </x-slot:header>

                <div class="crm-stack">
                    @forelse($topOpenDeals as $deal)
                        <div class="crm-list-item">
                            <strong><a href="{{ route('crm.deals.show', $deal) }}">{{ $deal->title }}</a></strong>
                            <span>{{ $money($deal->value) }} / {{ $deal->stage?->name ?: '-' }} / {{ $deal->owner?->name ?: '-' }}</span>
                        </div>
                    @empty
                        <p class="crm-muted">No open deals.</p>
                    @endforelse
                </div>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    Quote Status Distribution
                </x-slot:header>

                <div class="crm-stack">
                    @foreach($quoteStatusDistribution as $status)
                        <div class="crm-dashboard-row">
                            <div class="crm-dashboard-row-head">
                                <strong>{{ $status['label'] }}</strong>
                                <span>{{ $status['total'] }}</span>
                            </div>
                            <div class="crm-dashboard-bar">
                                <span style="width: {{ min(100, round(($status['total'] / $maxQuoteStatus) * 100)) }}%"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin-panel::card>
        </div>
    </section>
@endsection
