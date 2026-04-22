@extends('admin-panel::layouts.app')

@section('title', $deal->title)
@section('page-title', $deal->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="deals">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Deals</p>
                <h1>{{ $deal->title }}</h1>
            </div>

            <div class="crm-admin-actions">
                @can('update', $deal)
                    <x-admin-panel::button :href="route('crm.deals.edit', $deal)" icon="pencil">
                        Edit
                    </x-admin-panel::button>
                @endcan
                <x-admin-panel::button :href="route('crm.deals.index')" variant="ghost" icon="arrow-left">
                    Back
                </x-admin-panel::button>
            </div>
        </header>

        <div class="crm-admin-grid">
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Value</span>
                <strong>{{ $deal->currency }} {{ number_format((float) $deal->value, 2) }}</strong>
                <p>{{ $deal->probability }}% probability</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Weighted Value</span>
                <strong>{{ $deal->currency }} {{ number_format($weightedValue, 2) }}</strong>
                <p>{{ ucfirst($deal->status) }}</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Stage</span>
                <strong>{{ $deal->stage?->name ?: '-' }}</strong>
                <p>{{ $deal->expected_close_date?->format('Y-m-d') ?: 'No expected close date' }}</p>
            </div>
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Deal Summary
                </x-slot:header>

                <dl class="crm-detail-list">
                    <dt>Company</dt>
                    <dd>{{ $deal->company?->name ?: '-' }}</dd>
                    <dt>Contact</dt>
                    <dd>{{ $deal->contact?->full_name ?: '-' }}</dd>
                    <dt>Owner</dt>
                    <dd>{{ $deal->owner?->name ?: '-' }}</dd>
                    <dt>Closed At</dt>
                    <dd>{{ $deal->closed_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                    <dt>Lost Reason</dt>
                    <dd>{{ $deal->lost_reason ?: '-' }}</dd>
                    <dt>Tags</dt>
                    <dd>{{ $deal->tags->pluck('name')->implode(', ') ?: '-' }}</dd>
                </dl>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    Open Tasks
                </x-slot:header>

                <div class="crm-stack">
                    @forelse($openTasks as $task)
                        <div class="crm-list-item">
                            <strong>{{ $task->title }}</strong>
                            <span>{{ $task->due_at?->format('Y-m-d H:i') ?: 'No due date' }} / {{ $task->assignee?->name ?: 'Unassigned' }}</span>
                        </div>
                    @empty
                        <p class="crm-muted">No open tasks.</p>
                    @endforelse
                </div>
            </x-admin-panel::card>
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Quotes
                </x-slot:header>

                <div class="crm-stack">
                    @forelse($deal->quotes as $quote)
                        <div class="crm-list-item">
                            <strong>{{ $quote->quote_number }}</strong>
                            <span>{{ ucfirst($quote->status) }} / {{ $quote->currency }} {{ number_format((float) $quote->grand_total, 2) }}</span>
                        </div>
                    @empty
                        <p class="crm-muted">No quotes yet.</p>
                    @endforelse
                </div>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    Activity Timeline
                </x-slot:header>

                <div class="crm-stack">
                    @forelse($timeline as $activity)
                        <div class="crm-timeline-item">
                            <strong>{{ $activity->subject }}</strong>
                            <span>{{ ucfirst($activity->type) }} / {{ $activity->occurred_at?->format('Y-m-d H:i') }}</span>
                            @if($activity->body)
                                <p>{{ $activity->body }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="crm-muted">No activities yet.</p>
                    @endforelse
                </div>
            </x-admin-panel::card>
        </div>
    </section>
@endsection
