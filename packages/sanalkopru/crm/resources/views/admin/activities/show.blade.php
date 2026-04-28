@extends('admin-panel::layouts.app')

@section('title', $activity->subject)
@section('page-title', $activity->subject)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $related = $activity->activityable;
        $relatedRoute = match(true) {
            $related instanceof \Sanalkopru\Crm\Models\Contact => route('crm.contacts.show', $related),
            $related instanceof \Sanalkopru\Crm\Models\Company => route('crm.companies.show', $related),
            $related instanceof \Sanalkopru\Crm\Models\Deal   => route('crm.deals.show', $related),
            $related instanceof \Sanalkopru\Crm\Models\Quote  => route('crm.quotes.show', $related),
            default => null,
        };
        $relatedLabel = match(true) {
            $related instanceof \Sanalkopru\Crm\Models\Contact => $related->full_name,
            $related instanceof \Sanalkopru\Crm\Models\Company => $related->name,
            $related instanceof \Sanalkopru\Crm\Models\Deal   => $related->title,
            $related instanceof \Sanalkopru\Crm\Models\Quote  => $related->quote_number,
            default => '-',
        };
        $typeVariant = match($activity->type) {
            'note' => 'info',
            'call' => 'success',
            'email' => 'primary',
            'meeting' => 'warning',
            default => 'secondary',
        };
        $typeLabel = $crmFormat->activityType($activity->type);
    @endphp

    <section class="crm-admin-page" data-crm-module="activities">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Activities</p>
                <h1>{{ $activity->subject }}</h1>
                <p class="crm-muted" style="margin-top:4px;">
                    <x-admin-panel::badge :variant="$typeVariant">{{ $typeLabel }}</x-admin-panel::badge>
                </p>
            </div>
            <div class="crm-admin-actions">
                @can('update', $activity)
                    <x-admin-panel::button :href="route('crm.activities.edit', $activity)" icon="pencil">Edit</x-admin-panel::button>
                @endcan
                <x-admin-panel::button :href="route('crm.activities.index')" variant="ghost" icon="arrow-left">Back</x-admin-panel::button>
            </div>
        </header>

        <x-admin-panel::card>
            <x-slot:header>Activity Detail</x-slot:header>

            <dl class="crm-detail-list">
                <dt>Type</dt>
                <dd><x-admin-panel::badge :variant="$typeVariant">{{ $typeLabel }}</x-admin-panel::badge></dd>
                <dt>Related</dt>
                <dd>
                    @if($relatedRoute)
                        <a href="{{ $relatedRoute }}" style="color:#0369a1;">{{ $relatedLabel }}</a>
                    @else
                        {{ $relatedLabel }}
                    @endif
                </dd>
                <dt>Logged by</dt>
                <dd>{{ $activity->user?->name ?: 'System' }}</dd>
                <dt>Occurred</dt>
                <dd>
                    {{ $activity->occurred_at?->format('d M Y H:i') ?: '-' }}
                    @if($activity->occurred_at)
                        <span class="crm-muted">({{ $activity->occurred_at->diffForHumans() }})</span>
                    @endif
                </dd>
                @if($activity->body)
                    <dt>Notes</dt>
                    <dd>{{ $activity->body }}</dd>
                @endif
            </dl>
        </x-admin-panel::card>
    </section>
@endsection
