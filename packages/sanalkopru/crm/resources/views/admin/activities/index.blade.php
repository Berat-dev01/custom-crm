@extends('admin-panel::layouts.app')

@section('title', 'Activities')
@section('page-title', 'Activities')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $activeFilterCount = collect($filters)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input" aria-label="Select all activities">'), 'width' => '36px'],
            ['label' => 'Activity'],
            ['label' => 'Type'],
            ['label' => 'Related'],
            ['label' => 'User'],
            ['label' => 'Occurred'],
            ['label' => 'Actions', 'width' => '180px'],
        ];
    @endphp

    <section class="crm-admin-page" data-crm-module="activities">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM</p>
                <h1>Activities</h1>
            </div>

            @can('crm.activities.create')
                <x-admin-panel::button :href="route('crm.activities.create')" icon="plus">New Activity</x-admin-panel::button>
            @endcan
        </header>

        <div id="crm-activities-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('crm.activities.index')" :reset-url="route('crm.activities.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Subject or body" />
                    <x-admin-panel::select name="type" label="Type" :options="$types" :selected="$filters['type']" placeholder="All types" />
                    <x-admin-panel::select name="user_id" label="User" :options="$users" :selected="$filters['user_id']" placeholder="All users" />
                </x-slot:compact>

                <x-slot:advanced>
                    <x-admin-panel::select name="activityable_type" label="Related Type" :options="$activityableTypes" :selected="$filters['activityable_type']" placeholder="All related records" />
                    <x-admin-panel::input name="occurred_from" label="Occurred From" type="date" :value="$filters['occurred_from']" />
                    <x-admin-panel::input name="occurred_to" label="Occurred To" type="date" :value="$filters['occurred_to']" />
                </x-slot:advanced>

                <x-slot:saved>
                    @include('crm::admin.partials.saved-filters', ['module' => 'activities', 'savedFilters' => $savedFilters, 'filters' => $filters])
                </x-slot:saved>
            </x-admin-panel::filter-shell>

            <form id="crm-activity-bulk" method="POST" action="{{ route('crm.activities.bulk-delete') }}">
                @csrf
                @method('DELETE')

                <x-admin-panel::bulk-actions form="crm-activity-bulk" checkbox-selector=".crm-activity-selector" label="activities">
                    @can('crm.activities.delete')
                        <x-admin-panel::button
                            type="submit"
                            size="sm"
                            variant="danger"
                            icon="trash-2"
                            form="crm-activity-bulk"
                            data-crm-confirm="Delete selected activities?"
                        >
                            Delete Selected
                        </x-admin-panel::button>
                    @endcan
                </x-admin-panel::bulk-actions>

                <x-admin-panel::card>
                    <x-slot:header>
                        Timeline
                    </x-slot:header>

                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($activities as $activity)
                    @php
                        $related = $activity->activityable;
                        $relatedLabel = match(true) {
                            $related instanceof \Sanalkopru\Crm\Models\Contact => $related->full_name,
                            $related instanceof \Sanalkopru\Crm\Models\Company => $related->name,
                            $related instanceof \Sanalkopru\Crm\Models\Deal => $related->title,
                            $related instanceof \Sanalkopru\Crm\Models\Quote => $related->quote_number,
                            default => '-',
                        };
                    @endphp
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                name="record_ids[]"
                                value="{{ $activity->id }}"
                                class="form-check-input crm-activity-selector"
                            >
                        </td>
                        <td>
                            <strong>{{ $activity->subject }}</strong>
                            <div class="crm-muted">{{ $activity->body ? str($activity->body)->limit(80) : 'No body' }}</div>
                        </td>
                        <td>{{ $crmFormat->status($activity->type) }}</td>
                        <td>{{ $relatedLabel }}</td>
                        <td>{{ $activity->user?->name ?: 'System' }}</td>
                        <td>{{ $crmFormat->datetime($activity->occurred_at) }}</td>
                        <td>
                            <div class="crm-row-actions">
                                <x-admin-panel::button :href="route('crm.activities.show', $activity)" size="sm" variant="ghost" icon="eye" />
                                @can('update', $activity)
                                    <x-admin-panel::button :href="route('crm.activities.edit', $activity)" size="sm" variant="ghost" icon="pencil" />
                                @endcan
                                @can('delete', $activity)
                                    <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="crm-activity-delete-{{ $activity->id }}" data-crm-confirm="Delete this activity?" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            @include('crm::admin.partials.empty-state', [
                                'title' => 'No activities found.',
                                'body' => 'Log calls, meetings and notes to keep the customer timeline useful.',
                                'actionUrl' => route('crm.activities.create'),
                                'actionLabel' => 'New Activity',
                                'actionPermission' => 'crm.activities.create',
                            ])
                        </td>
                    </tr>
                    @endforelse
                    </x-admin-panel::table>

                    <x-admin-panel::pagination :paginator="$activities" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>

        @foreach($activities as $activity)
            @can('delete', $activity)
                <form id="crm-activity-delete-{{ $activity->id }}" method="POST" action="{{ route('crm.activities.destroy', $activity) }}" class="crm-hidden-form">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
        @endforeach
    </section>
@endsection
