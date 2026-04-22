@extends('admin-panel::layouts.app')

@section('title', $contact->full_name)
@section('page-title', $contact->full_name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="contacts">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Contacts</p>
                <h1>{{ $contact->full_name }}</h1>
                <p class="crm-muted">{{ $contact->title ?: 'No title' }}{{ $contact->company ? ' at '.$contact->company->name : '' }}</p>
            </div>

            <div class="crm-admin-actions">
                @can('update', $contact)
                    <x-admin-panel::button :href="route('crm.contacts.edit', $contact)" icon="pencil">
                        Edit
                    </x-admin-panel::button>
                @endcan
                <x-admin-panel::button :href="route('crm.contacts.index')" variant="ghost" icon="arrow-left">
                    Back
                </x-admin-panel::button>
            </div>
        </header>

        <div class="crm-admin-grid">
            <x-admin-panel::stat-card label="Open Deal Value" :value="number_format($openDealsValue, 2).' '.config('crm.money.default_currency')" icon="circle-dollar-sign" variant="success" />
            <x-admin-panel::stat-card label="Deals" :value="$contact->deals->count()" icon="kanban-square" variant="primary" />
            <x-admin-panel::stat-card label="Open Tasks" :value="$openTasks->count()" icon="check-square" variant="warning" />
            <x-admin-panel::stat-card label="Quotes" :value="$contact->quotes->count()" icon="file-text" variant="info" />
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>Profile</x-slot:header>
                <dl class="crm-detail-list">
                    <dt>Email</dt><dd>{{ $contact->email ?: '-' }}</dd>
                    <dt>Phone</dt><dd>{{ $contact->phone ?: '-' }}</dd>
                    <dt>Lifecycle</dt><dd>{{ ucfirst($contact->lifecycle_stage) }}</dd>
                    <dt>Source</dt><dd>{{ $contact->source ?: '-' }}</dd>
                    <dt>Owner</dt><dd>{{ $contact->owner?->name ?: '-' }}</dd>
                    <dt>Last contacted</dt><dd>{{ $contact->last_contacted_at?->diffForHumans() ?: '-' }}</dd>
                    <dt>Tags</dt>
                    <dd>
                        @forelse($contact->tags as $tag)
                            <x-admin-panel::badge variant="secondary">{{ $tag->name }}</x-admin-panel::badge>
                        @empty
                            -
                        @endforelse
                    </dd>
                </dl>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Quick Note</x-slot:header>
                @can('crm.activities.create')
                    <form method="POST" action="{{ route('crm.contacts.notes.store', $contact) }}" class="crm-stack">
                        @csrf
                        <x-admin-panel::textarea name="body" label="Note" rows="5" required />
                        <x-admin-panel::button type="submit" icon="message-square">Add Note</x-admin-panel::button>
                    </form>
                @endcan
            </x-admin-panel::card>
        </div>

        <div class="crm-three-column">
            <x-admin-panel::card>
                <x-slot:header>Deals</x-slot:header>
                @forelse($contact->deals->sortByDesc('created_at') as $deal)
                    <div class="crm-list-item">
                        <strong>{{ $deal->title }}</strong>
                        <span>{{ number_format((float) $deal->value, 2) }} {{ $deal->currency }} / {{ $deal->stage?->name }}</span>
                    </div>
                @empty
                    <p class="crm-muted">No deals yet.</p>
                @endforelse
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Tasks</x-slot:header>
                @forelse($openTasks as $task)
                    <div class="crm-list-item">
                        <strong>{{ $task->title }}</strong>
                        <span>{{ $task->due_at?->diffForHumans() ?: 'No due date' }}</span>
                    </div>
                @empty
                    <p class="crm-muted">No open tasks.</p>
                @endforelse
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Quotes</x-slot:header>
                @forelse($contact->quotes->sortByDesc('created_at') as $quote)
                    <div class="crm-list-item">
                        <strong><a href="{{ route('crm.quotes.show', $quote) }}">{{ $quote->quote_number }}</a></strong>
                        <span>{{ ucfirst($quote->status) }} / {{ number_format((float) $quote->grand_total, 2) }} {{ $quote->currency }}</span>
                    </div>
                @empty
                    <p class="crm-muted">No quotes yet.</p>
                @endforelse
            </x-admin-panel::card>
        </div>

        <x-admin-panel::card>
            <x-slot:header>Timeline</x-slot:header>
            @forelse($timeline as $activity)
                <div class="crm-timeline-item">
                    <div>
                        <x-admin-panel::badge variant="info">{{ ucfirst($activity->type) }}</x-admin-panel::badge>
                        <strong>{{ $activity->subject }}</strong>
                    </div>
                    <p>{{ $activity->body }}</p>
                    <span>{{ $activity->occurred_at?->diffForHumans() ?: '-' }} by {{ $activity->user?->name ?: 'System' }}</span>
                </div>
            @empty
                <p class="crm-muted">No activity yet.</p>
            @endforelse
        </x-admin-panel::card>
    </section>
@endsection
