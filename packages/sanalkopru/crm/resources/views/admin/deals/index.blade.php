@extends('admin-panel::layouts.app')

@section('title', 'Deals')
@section('page-title', 'Deals')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $activeFilterCount = collect($filters)
            ->except(['view'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input" aria-label="Select all deals">'), 'width' => '36px'],
            ['label' => 'Deal'],
            ['label' => 'Stage'],
            ['label' => 'Value'],
            ['label' => 'Expected Close'],
            ['label' => 'Owner'],
            ['label' => 'Actions', 'width' => '220px'],
        ];
    @endphp

    <section class="crm-admin-page" data-crm-module="deals">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM</p>
                <h1>Deals Pipeline</h1>
            </div>

            <div class="crm-admin-actions">
                <div class="crm-view-switch">
                    <x-admin-panel::button :href="route('crm.deals.index', array_merge(request()->except('view', 'page'), ['view' => 'kanban']))" variant="{{ $filters['view'] === 'list' ? 'ghost' : 'outline' }}" icon="columns-3" data-admin-ajax-link data-admin-ajax-target="crm-deals-list">
                        Kanban
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.deals.index', array_merge(request()->except('view', 'page'), ['view' => 'list']))" variant="{{ $filters['view'] === 'list' ? 'outline' : 'ghost' }}" icon="list" data-admin-ajax-link data-admin-ajax-target="crm-deals-list">
                        List
                    </x-admin-panel::button>
                </div>
                @can('crm.deals.import')
                    <x-admin-panel::button :href="route('crm.deals.template')" variant="ghost" icon="download">
                        Template
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.deals.import')" variant="outline" icon="upload">
                        Import
                    </x-admin-panel::button>
                @endcan
                @can('crm.deals.export')
                    <x-admin-panel::export-button
                        :url="route('crm.deals.export')"
                        :columns="$exportColumns"
                        :formats="$exportFormats"
                        module="deals"
                    />
                @endcan
                @can('crm.deals.create')
                    <x-admin-panel::button :href="route('crm.deals.create')" icon="plus">
                        New Deal
                    </x-admin-panel::button>
                @endcan
            </div>
        </header>

        <div id="crm-deals-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('crm.deals.index')" :reset-url="route('crm.deals.index', ['view' => $filters['view']])" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <input type="hidden" name="view" value="{{ $filters['view'] }}">
                    <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Title, company or contact" />
                    <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$filters['owner_id']" placeholder="All owners" />
                    <x-admin-panel::select name="status" label="Status" :options="$statuses" :selected="$filters['status']" placeholder="All statuses" />
                </x-slot:compact>

                <x-slot:advanced>
                    <x-admin-panel::select name="tag_id" label="Tag" :options="$tags" :selected="$filters['tag_id']" placeholder="All tags" />
                    <x-admin-panel::input name="expected_from" label="Expected From" type="date" :value="$filters['expected_from']" />
                    <x-admin-panel::input name="expected_to" label="Expected To" type="date" :value="$filters['expected_to']" />
                    <x-admin-panel::input name="value_min" label="Min Value" type="number" min="0" step="0.01" :value="$filters['value_min']" />
                    <x-admin-panel::input name="value_max" label="Max Value" type="number" min="0" step="0.01" :value="$filters['value_max']" />
                </x-slot:advanced>

                <x-slot:saved>
                    @include('crm::admin.partials.saved-filters', ['module' => 'deals', 'savedFilters' => $savedFilters, 'filters' => $filters])
                </x-slot:saved>
            </x-admin-panel::filter-shell>

            @if($filters['view'] === 'list')
                <form id="crm-deal-bulk" method="POST" action="{{ route('crm.deals.bulk-delete') }}">
                    @csrf
                    @method('DELETE')

                    <x-admin-panel::bulk-actions form="crm-deal-bulk" checkbox-selector=".crm-deal-selector" label="deals">
                        @can('crm.deals.delete')
                            <x-admin-panel::button
                                type="submit"
                                size="sm"
                                variant="danger"
                                icon="trash-2"
                                form="crm-deal-bulk"
                                data-crm-confirm="Delete selected deals?"
                            >
                                Delete Selected
                            </x-admin-panel::button>
                        @endcan
                    </x-admin-panel::bulk-actions>

                    <x-admin-panel::card>
                        <x-slot:header>
                            Deals
                        </x-slot:header>

                        <x-admin-panel::table :headers="$tableHeaders">
                        @forelse($deals as $deal)
                            <tr>
                                <td>
                                    <input
                                        type="checkbox"
                                        name="record_ids[]"
                                        value="{{ $deal->id }}"
                                        class="form-check-input crm-deal-selector"
                                    >
                                </td>
                                <td>
                                    <strong>{{ $deal->title }}</strong>
                                    <div class="crm-muted">{{ $deal->company?->name ?: $deal->contact?->full_name ?: 'No account linked' }}</div>
                                </td>
                                <td>{{ $deal->stage?->name ?: '-' }}</td>
                                <td>{{ $crmFormat->money($deal->value, $deal->currency) }}</td>
                                <td>{{ $crmFormat->date($deal->expected_close_date) }}</td>
                                <td>{{ $deal->owner?->name ?: '-' }}</td>
                                <td>
                                    <div class="crm-row-actions">
                                        <x-admin-panel::button :href="route('crm.deals.show', $deal)" size="sm" variant="ghost" icon="eye" />
                                        @can('update', $deal)
                                            <x-admin-panel::button :href="route('crm.deals.edit', $deal)" size="sm" variant="ghost" icon="pencil" />
                                        @endcan
                                        @can('delete', $deal)
                                            <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="crm-deal-delete-{{ $deal->id }}" data-crm-confirm="Delete this deal?" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    @include('crm::admin.partials.empty-state', [
                                        'title' => 'No deals found.',
                                        'body' => 'Start a new opportunity or relax the current filters.',
                                        'actionUrl' => route('crm.deals.create'),
                                        'actionLabel' => 'New Deal',
                                        'actionPermission' => 'crm.deals.create',
                                    ])
                                </td>
                            </tr>
                        @endforelse
                        </x-admin-panel::table>

                        <x-admin-panel::pagination :paginator="$deals" class="crm-pagination" />
                    </x-admin-panel::card>
                </form>
            @else
                <div class="crm-kanban-scroll">
                    <div class="crm-kanban-board" data-crm-kanban-board>
                    @foreach($pipeline as $stage)
                        <section class="crm-kanban-column" data-crm-kanban-column="{{ $stage->id }}">
                            <header class="crm-kanban-column-header">
                                <div class="crm-kanban-column-title">
                                    <h2>
                                        <span class="crm-color-swatch" style="background: {{ $stage->color }}"></span>
                                        {{ $stage->name }}
                                    </h2>
                                    @if($stage->is_won)
                                        <span class="crm-stage-kind crm-stage-kind-won">Won</span>
                                    @elseif($stage->is_lost)
                                        <span class="crm-stage-kind crm-stage-kind-lost">Lost</span>
                                    @endif
                                </div>
                                <div class="crm-kanban-column-meta">
                                    <span data-crm-stage-count>{{ $stage->deals_count }} deals</span>
                                    <span data-crm-stage-value>{{ $crmFormat->money($stage->pipeline_value) }}</span>
                                </div>
                            </header>

                            <div
                                class="crm-kanban-list"
                                data-crm-kanban-list
                                data-stage-id="{{ $stage->id }}"
                                data-stage-is-lost="{{ $stage->is_lost ? '1' : '0' }}"
                            >
                                @forelse($stage->deals as $deal)
                                    <article
                                        class="crm-kanban-card"
                                        data-deal-id="{{ $deal->id }}"
                                        data-move-url="{{ route('crm.deals.move', $deal) }}"
                                    >
                                        <a href="{{ route('crm.deals.show', $deal) }}" class="crm-kanban-card-title">
                                            {{ $deal->title }}
                                        </a>
                                        <div class="crm-kanban-card-meta">
                                            <span>{{ $deal->company?->name ?: $deal->contact?->full_name ?: 'No account' }}</span>
                                            <span>{{ $crmFormat->money($deal->value, $deal->currency) }}</span>
                                        </div>
                                        <div class="crm-kanban-card-footer">
                                            <span>{{ $deal->expected_close_date ? $crmFormat->date($deal->expected_close_date) : 'No close date' }}</span>
                                            <span>{{ $deal->owner?->name ?: 'No owner' }}</span>
                                            @if($deal->open_tasks_count > 0)
                                                <span class="crm-kanban-badge">{{ $deal->open_tasks_count }} tasks</span>
                                            @endif
                                            @can('delete', $deal)
                                                <form method="POST" action="{{ route('crm.deals.destroy', $deal) }}" class="crm-inline-form" data-crm-confirm="Delete this deal?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" />
                                                </form>
                                            @endcan
                                        </div>
                                    </article>
                                @empty
                                    @include('crm::admin.partials.empty-state', [
                                        'title' => 'No deals.',
                                        'body' => 'Drag deals here as the pipeline develops.',
                                    ])
                                @endforelse
                                @if($stage->has_more_deals)
                                    <div class="crm-kanban-more">
                                        Showing {{ $stage->deals->count() }} of {{ $stage->deals_count }} deals. Use filters or list view for the full stage.
                                    </div>
                                @endif
                            </div>
                        </section>
                    @endforeach
                    </div>
                </div>

                <dialog class="crm-dialog" data-crm-lost-dialog>
                    <form method="dialog" class="crm-dialog-body">
                        <h2>Lost Reason</h2>
                        <div class="form-group">
                            <label class="form-label" for="crm-lost-reason">Reason</label>
                            <textarea id="crm-lost-reason" name="lost_reason" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="crm-dialog-actions">
                            <x-admin-panel::button type="button" variant="ghost" onclick="this.closest('dialog').close('cancel')">
                                Cancel
                            </x-admin-panel::button>
                            <x-admin-panel::button type="submit" icon="save">
                                Save
                            </x-admin-panel::button>
                        </div>
                    </form>
                </dialog>
            @endif
        </div>

        @foreach($deals as $deal)
            @can('delete', $deal)
                <form id="crm-deal-delete-{{ $deal->id }}" method="POST" action="{{ route('crm.deals.destroy', $deal) }}" class="crm-hidden-form">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
        @endforeach
    </section>
@endsection
