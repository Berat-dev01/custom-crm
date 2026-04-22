@extends('admin-panel::layouts.app')

@section('title', $activity->subject)
@section('page-title', $activity->subject)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
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

    <section class="crm-admin-page" data-crm-module="activities">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Activities</p>
                <h1>{{ $activity->subject }}</h1>
            </div>
            <div class="crm-admin-actions">
                @can('update', $activity)
                    <x-admin-panel::button :href="route('crm.activities.edit', $activity)" icon="pencil">Edit</x-admin-panel::button>
                @endcan
                <x-admin-panel::button :href="route('crm.activities.index')" variant="ghost" icon="arrow-left">Back</x-admin-panel::button>
            </div>
        </header>

        <x-admin-panel::card>
            <x-slot:header>
                Activity Detail
            </x-slot:header>

            <dl class="crm-detail-list">
                <dt>Type</dt>
                <dd>{{ ucfirst(str_replace('_', ' ', $activity->type)) }}</dd>
                <dt>Related</dt>
                <dd>{{ $relatedLabel }}</dd>
                <dt>User</dt>
                <dd>{{ $activity->user?->name ?: 'System' }}</dd>
                <dt>Occurred</dt>
                <dd>{{ $activity->occurred_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                <dt>Body</dt>
                <dd>{{ $activity->body ?: '-' }}</dd>
            </dl>
        </x-admin-panel::card>
    </section>
@endsection
