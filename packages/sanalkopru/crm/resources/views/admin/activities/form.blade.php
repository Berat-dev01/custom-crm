@extends('admin-panel::layouts.app')

@section('title', $activity->exists ? 'Edit Activity' : 'New Activity')
@section('page-title', $activity->exists ? 'Edit Activity' : 'New Activity')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="activities">
        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Activities</p>
                <h1>{{ $activity->exists ? 'Edit Activity' : 'New Activity' }}</h1>
            </div>
            <x-admin-panel::button :href="route('crm.activities.index')" variant="ghost" icon="arrow-left">Back</x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <form
                method="POST"
                action="{{ $activity->exists ? route('crm.activities.update', $activity) : route('crm.activities.store') }}"
                class="crm-form-grid"
            >
                @csrf
                @if($activity->exists)
                    @method('PUT')
                @endif

                @unless($activity->exists)
                    <x-admin-panel::select name="activityable_type" label="Related Type" :options="$activityableTypes" :selected="$selectedActivityableType" required />
                    <x-admin-panel::input name="activityable_id" label="Related Record ID" type="number" :value="$activity->activityable_id" required />
                @endunless

                <x-admin-panel::select name="type" label="Type" :options="$types" :selected="$activity->type ?: 'note'" required />
                <x-admin-panel::input name="subject" label="Subject" :value="$activity->subject" required />
                <x-admin-panel::input name="occurred_at" label="Occurred At" type="datetime-local" :value="$activity->occurred_at?->format('Y-m-d\\TH:i')" />

                @unless($activity->exists)
                    <div class="crm-span-2 crm-highlight-box">
                        <strong>Available related records</strong>
                        <p class="crm-muted">Contacts: {{ $activityableOptions['contact']->pluck('full_name', 'id')->map(fn($name, $id) => "#{$id} {$name}")->implode(', ') ?: '-' }}</p>
                        <p class="crm-muted">Companies: {{ $activityableOptions['company']->pluck('name', 'id')->map(fn($name, $id) => "#{$id} {$name}")->implode(', ') ?: '-' }}</p>
                        <p class="crm-muted">Deals: {{ $activityableOptions['deal']->pluck('title', 'id')->map(fn($name, $id) => "#{$id} {$name}")->implode(', ') ?: '-' }}</p>
                        <p class="crm-muted">Quotes: {{ $activityableOptions['quote']->pluck('quote_number', 'id')->map(fn($name, $id) => "#{$id} {$name}")->implode(', ') ?: '-' }}</p>
                    </div>
                @endunless

                <x-admin-panel::textarea name="body" label="Body" class="crm-span-2" :value="$activity->body" rows="6" />

                <div class="crm-form-actions crm-span-2">
                    <x-admin-panel::button type="submit" icon="save">
                        {{ $activity->exists ? 'Save Activity' : 'Create Activity' }}
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.activities.index')" variant="ghost">Cancel</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>
    </section>
@endsection
