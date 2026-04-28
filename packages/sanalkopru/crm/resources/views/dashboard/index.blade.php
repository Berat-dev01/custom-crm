@extends('admin-panel::layouts.app')

@section('title', __('CRM Dashboard'))
@section('page-title', __('CRM Dashboard'))

@push('styles')
    @php
        $crmCssUrl = asset('vendor/crm/css/crm.css') . '?v=' . (@filemtime(public_path('vendor/crm/css/crm.css')) ?: time());
    @endphp
    <link rel="stylesheet" href="{{ $crmCssUrl }}">
@endpush

@section('content')
    @php
        $money = fn (float|int|string|null $value, ?string $currency = null): string => $crmFormat->money($value, $currency);
        $maxPipeline = max(1, $pipelineByStage->max('pipeline_value') ?: 1);
        $maxQuoteStatus = max(1, $quoteStatusDistribution->max('total') ?: 1);
        $maxTrend = max(1, collect($monthlyTrend)->max(fn($row) => max($row['won_count'], $row['lost_count'])) ?: 1);
        $periodOptions = [
            'today' => __('Today'),
            'this_week' => __('This Week'),
            'this_month' => __('This Month'),
            'custom' => __('Custom'),
        ];
    @endphp

    <section class="crm-admin-page" data-crm-module="dashboard">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-dashboard-header">
            <div>
                <h1>{{ __('Sales Dashboard') }}</h1>
                <p class="crm-dashboard-meta">
                    <span>{{ $canViewAll ? __('All records') : __('Your records') }}</span>
                    <span class="crm-dashboard-meta-sep">·</span>
                    <span>{{ $range['label'] }}</span>
                    <span class="crm-dashboard-meta-sep">·</span>
                    <span>{{ __('Period filters affect activity metrics; snapshot metrics are always live') }}</span>
                </p>
            </div>
        </header>

        <div id="crm-dashboard-region" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('crm.dashboard')" :reset-url="route('crm.dashboard')" :active-count="collect($filters)->filter(fn ($value) => $value !== null && $value !== '' && $value !== 'this_month')->count()">
                <x-slot:compact>
                    <x-admin-panel::select name="period" label="Period" :options="$periodOptions" :selected="$filters['period']" />
                    <x-admin-panel::input name="date_from" label="From" type="date" :value="$filters['date_from']" />
                    <x-admin-panel::input name="date_to" label="To" type="date" :value="$filters['date_to']" />
                </x-slot:compact>
            </x-admin-panel::filter-shell>

            <div class="crm-dashboard-section-label">
                {{ __('Current snapshot') }}
                <span class="crm-dashboard-section-hint">{{ __('live') }}</span>
            </div>

            <div class="crm-admin-grid">
                <x-admin-panel::stat-card :label="__('Contacts')" :value="number_format($stats['contacts'])" icon="users" variant="primary" data-crm-count-up="{{ $stats['contacts'] }}" />
                <x-admin-panel::stat-card :label="__('Companies')" :value="number_format($stats['companies'])" icon="building-2" variant="info" data-crm-count-up="{{ $stats['companies'] }}" />
                <x-admin-panel::stat-card :label="__('Open Deals')" :value="number_format($stats['open_deals'])" icon="briefcase" variant="primary" data-crm-count-up="{{ $stats['open_deals'] }}" />
                <x-admin-panel::stat-card :label="__('Open Pipeline')" :value="$money($stats['open_pipeline_value'])" icon="chart-no-axes-column" variant="success" />
                <x-admin-panel::stat-card :label="__('Weighted Pipeline')" :value="$money($stats['weighted_pipeline_value'])" icon="percent" variant="info" />
                <x-admin-panel::stat-card :label="__('Overdue Tasks')" :value="number_format($stats['overdue_tasks'])" icon="alarm-clock" variant="{{ $stats['overdue_tasks'] > 0 ? 'danger' : 'success' }}" data-crm-count-up="{{ $stats['overdue_tasks'] }}" />
            </div>

            <div class="crm-dashboard-section-label">
                {{ __('Period activity') }}
                <span class="crm-dashboard-section-hint">{{ $range['label'] }}</span>
            </div>

            <div class="crm-admin-grid">
                <x-admin-panel::stat-card :label="__('Won Value')" :value="$money($stats['won_deal_value'])" icon="trophy" variant="success" />
                <x-admin-panel::stat-card :label="__('Quotes Sent / Accepted')" :value="number_format($stats['sent_quotes']).' / '.number_format($stats['accepted_quotes'])" icon="file-text" variant="info" />
            </div>

            <div class="crm-dashboard-masonry">
                {{-- Left column: Pipeline → Tasks → Deals --}}
                <div class="crm-dashboard-col">
                    <x-admin-panel::card class="crm-dashboard-card" data-crm-dashboard-card>
                        <x-slot:header>{{ __('Pipeline by Stage') }}</x-slot:header>
                        <x-slot:headerActions>
                            <button type="button" class="crm-dashboard-expand" data-crm-dashboard-expand title="{{ __('Expand') }}">
                                <i data-lucide="expand" width="15" height="15"></i>
                            </button>
                        </x-slot:headerActions>

                        <div class="crm-stack crm-dashboard-panel" data-crm-paginate data-crm-page-size="6">
                            @foreach($pipelineByStage as $stage)
                                <div class="crm-dashboard-row">
                                    <div class="crm-dashboard-row-head">
                                        <strong>
                                            <span class="crm-color-swatch" style="background: {{ $stage['color'] }}"></span>
                                            {{ $stage['name'] }}
                                        </strong>
                                        <span>{{ __(':count deals', ['count' => $stage['deals_count']]) }} / {{ $money($stage['pipeline_value']) }}</span>
                                    </div>
                                    <div class="crm-dashboard-bar">
                                        <span style="width: {{ min(100, round(($stage['pipeline_value'] / $maxPipeline) * 100)) }}%"></span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-admin-panel::card>

                    <x-admin-panel::card class="crm-dashboard-card" data-crm-dashboard-card>
                        <x-slot:header>{{ __('Upcoming Tasks') }}</x-slot:header>
                        <x-slot:headerActions>
                            <button type="button" class="crm-dashboard-expand" data-crm-dashboard-expand title="{{ __('Expand') }}">
                                <i data-lucide="expand" width="15" height="15"></i>
                            </button>
                        </x-slot:headerActions>

                        <div class="crm-stack crm-dashboard-panel" data-crm-paginate data-crm-page-size="6">
                            @forelse($upcomingTasks as $task)
                                <div class="crm-list-item">
                                    <strong>{{ $task->title }}</strong>
                                    <span>{{ $crmFormat->datetime($task->due_at) }} / {{ $crmFormat->status($task->priority) }} / {{ $task->assignee?->name ?: __('Unassigned') }}</span>
                                </div>
                            @empty
                                <p class="crm-muted">{{ __('No upcoming tasks.') }}</p>
                            @endforelse
                        </div>
                    </x-admin-panel::card>

                    <x-admin-panel::card class="crm-dashboard-card" data-crm-dashboard-card>
                        <x-slot:header>{{ __('Highest Value Open Deals') }}</x-slot:header>
                        <x-slot:headerActions>
                            <button type="button" class="crm-dashboard-expand" data-crm-dashboard-expand title="{{ __('Expand') }}">
                                <i data-lucide="expand" width="15" height="15"></i>
                            </button>
                        </x-slot:headerActions>

                        <div class="crm-stack crm-dashboard-panel" data-crm-paginate data-crm-page-size="6">
                            @forelse($topOpenDeals as $deal)
                                <div class="crm-list-item">
                                    <strong><a href="{{ route('crm.deals.show', $deal) }}">{{ $deal->title }}</a></strong>
                                    <span>{{ $money($deal->value) }} / {{ $deal->stage?->name ?: '-' }} / {{ $deal->owner?->name ?: '-' }}</span>
                                </div>
                            @empty
                                <p class="crm-muted">{{ __('No open deals.') }}</p>
                            @endforelse
                        </div>
                    </x-admin-panel::card>
                </div>

                {{-- Right column: Trend → Activities → Quotes --}}
                <div class="crm-dashboard-col">
                    <x-admin-panel::card class="crm-dashboard-card" data-crm-dashboard-card>
                        <x-slot:header>{{ __('Period Won/Lost Trend') }}</x-slot:header>
                        <x-slot:headerActions>
                            <button type="button" class="crm-dashboard-expand" data-crm-dashboard-expand title="{{ __('Expand') }}">
                                <i data-lucide="expand" width="15" height="15"></i>
                            </button>
                        </x-slot:headerActions>

                        <div class="crm-stack crm-dashboard-panel" data-crm-paginate data-crm-page-size="6">
                            @foreach($monthlyTrend as $month)
                                <div class="crm-dashboard-row">
                                    <div class="crm-dashboard-row-head">
                                        <strong>{{ $month['label'] }}</strong>
                                        <span>{{ __(':won won / :lost lost', ['won' => $month['won_count'], 'lost' => $month['lost_count']]) }}</span>
                                    </div>
                                    <div class="crm-dashboard-split-bars">
                                        <span class="is-won" style="width: {{ min(100, round(($month['won_count'] / $maxTrend) * 100)) }}%"></span>
                                        <span class="is-lost" style="width: {{ min(100, round(($month['lost_count'] / $maxTrend) * 100)) }}%"></span>
                                    </div>
                                    <div class="crm-muted">{{ __(':value won value', ['value' => $money($month['won_value'])]) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </x-admin-panel::card>

                    <x-admin-panel::card class="crm-dashboard-card" data-crm-dashboard-card>
                        <x-slot:header>{{ __('Recent Activities') }}</x-slot:header>
                        <x-slot:headerActions>
                            <button type="button" class="crm-dashboard-expand" data-crm-dashboard-expand title="{{ __('Expand') }}">
                                <i data-lucide="expand" width="15" height="15"></i>
                            </button>
                        </x-slot:headerActions>

                        <div class="crm-stack crm-dashboard-panel" data-crm-paginate data-crm-page-size="5">
                            @forelse($recentActivities as $activity)
                                <div class="crm-timeline-item">
                                    <strong>{{ $activity->subject }}</strong>
                                    <span>{{ $crmFormat->status($activity->type) }} / {{ $crmFormat->datetime($activity->occurred_at) }} / {{ $activity->user?->name ?: __('System') }}</span>
                                    @if($activity->body)
                                        <p>{{ $activity->body }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="crm-muted">{{ __('No activity in this period.') }}</p>
                            @endforelse
                        </div>
                    </x-admin-panel::card>

                    <x-admin-panel::card class="crm-dashboard-card" data-crm-dashboard-card>
                        <x-slot:header>{{ __('Quote Status Distribution') }}</x-slot:header>
                        <x-slot:headerActions>
                            <button type="button" class="crm-dashboard-expand" data-crm-dashboard-expand title="{{ __('Expand') }}">
                                <i data-lucide="expand" width="15" height="15"></i>
                            </button>
                        </x-slot:headerActions>

                        <div class="crm-stack crm-dashboard-panel" data-crm-paginate data-crm-page-size="6">
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
            </div>
        </div>
    </section>
@endsection
