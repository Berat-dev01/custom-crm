@extends('admin-panel::layouts.app')

@section('title', 'Deals')
@section('page-title', 'Deals')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('vendor/crm/js/crm.js') }}"></script>
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="deals">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM</p>
                <h1>Deals Pipeline</h1>
            </div>

            <div class="crm-admin-actions">
                <div class="crm-view-switch">
                    <x-admin-panel::button :href="route('crm.deals.index', array_merge(request()->except('view', 'page'), ['view' => 'kanban']))" variant="{{ $filters['view'] === 'list' ? 'ghost' : 'outline' }}" icon="columns-3">
                        Kanban
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.deals.index', array_merge(request()->except('view', 'page'), ['view' => 'list']))" variant="{{ $filters['view'] === 'list' ? 'outline' : 'ghost' }}" icon="list">
                        List
                    </x-admin-panel::button>
                </div>
                @can('crm.deals.create')
                    <x-admin-panel::button :href="route('crm.deals.create')" icon="plus">
                        New Deal
                    </x-admin-panel::button>
                @endcan
            </div>
        </header>

        <x-admin-panel::card>
            <form method="GET" action="{{ route('crm.deals.index') }}" class="crm-filter-grid">
                <input type="hidden" name="view" value="{{ $filters['view'] }}">
                <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Title, company or contact" />
                <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$filters['owner_id']" placeholder="All owners" />
                <x-admin-panel::select name="tag_id" label="Tag" :options="$tags" :selected="$filters['tag_id']" placeholder="All tags" />
                <x-admin-panel::select name="status" label="Status" :options="$statuses" :selected="$filters['status']" placeholder="All statuses" />
                <x-admin-panel::input name="expected_from" label="Expected From" type="date" :value="$filters['expected_from']" />
                <x-admin-panel::input name="expected_to" label="Expected To" type="date" :value="$filters['expected_to']" />
                <x-admin-panel::input name="value_min" label="Min Value" type="number" min="0" step="0.01" :value="$filters['value_min']" />
                <x-admin-panel::input name="value_max" label="Max Value" type="number" min="0" step="0.01" :value="$filters['value_max']" />

                <div class="crm-filter-actions">
                    <x-admin-panel::button type="submit" icon="search">Apply</x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.deals.index', ['view' => $filters['view']])" variant="ghost">Reset</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        @if($filters['view'] === 'list')
            <x-admin-panel::card>
                <x-slot:header>
                    Deals
                </x-slot:header>

                <x-admin-panel::table :headers="[
                    ['label' => 'Deal'],
                    ['label' => 'Stage'],
                    ['label' => 'Value'],
                    ['label' => 'Expected Close'],
                    ['label' => 'Owner'],
                    ['label' => 'Actions', 'width' => '150px'],
                ]">
                    @forelse($deals as $deal)
                        <tr>
                            <td>
                                <strong>{{ $deal->title }}</strong>
                                <div class="crm-muted">{{ $deal->company?->name ?: $deal->contact?->full_name ?: 'No account linked' }}</div>
                            </td>
                            <td>{{ $deal->stage?->name ?: '-' }}</td>
                            <td>{{ $deal->currency }} {{ number_format((float) $deal->value, 2) }}</td>
                            <td>{{ $deal->expected_close_date?->format('Y-m-d') ?: '-' }}</td>
                            <td>{{ $deal->owner?->name ?: '-' }}</td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('crm.deals.show', $deal)" size="sm" variant="ghost" icon="eye" />
                                    @can('update', $deal)
                                        <x-admin-panel::button :href="route('crm.deals.edit', $deal)" size="sm" variant="ghost" icon="pencil" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="crm-empty">No deals found.</td>
                        </tr>
                    @endforelse
                </x-admin-panel::table>

                <div class="crm-pagination">
                    {{ $deals->links() }}
                </div>
            </x-admin-panel::card>
        @else
            <div class="crm-kanban-scroll">
                <div class="crm-kanban-board" data-crm-kanban-board>
                    @foreach($pipeline as $stage)
                        <section class="crm-kanban-column">
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
                                    <span>{{ $stage->deals_count }} deals</span>
                                    <span>{{ config('crm.money.default_currency', 'TRY') }} {{ number_format((float) $stage->pipeline_value, 2) }}</span>
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
                                            <span>{{ $deal->currency }} {{ number_format((float) $deal->value, 2) }}</span>
                                        </div>
                                        <div class="crm-kanban-card-footer">
                                            <span>{{ $deal->expected_close_date?->format('Y-m-d') ?: 'No close date' }}</span>
                                            <span>{{ $deal->owner?->name ?: 'No owner' }}</span>
                                            @if($deal->open_tasks_count > 0)
                                                <span class="crm-kanban-badge">{{ $deal->open_tasks_count }} tasks</span>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div class="crm-empty">No deals.</div>
                                @endforelse
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
    </section>
@endsection
