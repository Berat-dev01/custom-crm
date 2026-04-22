@extends('admin-panel::layouts.app')

@section('title', 'Activities')
@section('page-title', 'Activities')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
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

        <x-admin-panel::card>
            <form method="GET" action="{{ route('crm.activities.index') }}" class="crm-filter-grid">
                <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Subject or body" />
                <x-admin-panel::select name="type" label="Type" :options="$types" :selected="$filters['type']" placeholder="All types" />
                <x-admin-panel::select name="activityable_type" label="Related Type" :options="$activityableTypes" :selected="$filters['activityable_type']" placeholder="All related records" />
                <x-admin-panel::select name="user_id" label="User" :options="$users" :selected="$filters['user_id']" placeholder="All users" />
                <x-admin-panel::input name="occurred_from" label="Occurred From" type="date" :value="$filters['occurred_from']" />
                <x-admin-panel::input name="occurred_to" label="Occurred To" type="date" :value="$filters['occurred_to']" />

                <div class="crm-filter-actions">
                    <x-admin-panel::button type="submit" icon="search">Apply</x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.activities.index')" variant="ghost">Reset</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        <x-admin-panel::card>
            <x-slot:header>
                Timeline
            </x-slot:header>

            <x-admin-panel::table :headers="[
                ['label' => 'Activity'],
                ['label' => 'Type'],
                ['label' => 'Related'],
                ['label' => 'User'],
                ['label' => 'Occurred'],
                ['label' => 'Actions', 'width' => '150px'],
            ]">
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
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
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

            <div class="crm-pagination">
                {{ $activities->links() }}
            </div>
        </x-admin-panel::card>
    </section>
@endsection
