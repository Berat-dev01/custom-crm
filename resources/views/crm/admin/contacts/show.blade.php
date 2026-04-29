@extends('crm::layouts.app')

@section('title', $contact->full_name)
@section('page-title', $contact->full_name)


@section('content')
    <section class="crm-admin-page" data-crm-module="contacts">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Contacts') }}</p>
                <h1>{{ $contact->full_name }}</h1>
                <p class="crm-muted">{{ $contact->title ?: __('No title') }}{{ $contact->company ? ' '.__('at').' '.$contact->company->name : '' }}</p>
            </div>

            <div class="crm-admin-actions">
                @can('update', $contact)
                    <x-admin-panel::button :href="route('crm.contacts.edit', $contact)" icon="pencil">
                        Edit
                    </x-admin-panel::button>
                @endcan
                @can('delete', $contact)
                    <form method="POST" action="{{ route('crm.contacts.destroy', $contact) }}" data-crm-confirm="{{ __('Delete this contact?') }}">
                        @csrf
                        @method('DELETE')
                        <x-admin-panel::button type="submit" variant="danger" icon="trash-2">
                            Delete
                        </x-admin-panel::button>
                    </form>
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
                    <dt>{{ __('Email') }}</dt><dd>{{ $contact->email ?: '-' }}</dd>
                    <dt>{{ __('Phone') }}</dt><dd>{{ $contact->phone ?: '-' }}</dd>
                    <dt>{{ __('Lifecycle') }}</dt><dd>{{ $crmFormat->status($contact->lifecycle_stage) }}</dd>
                    <dt>{{ __('Source') }}</dt><dd>{{ $contact->source ? $crmFormat->status($contact->source) : '-' }}</dd>
                    <dt>{{ __('Owner') }}</dt><dd>{{ $contact->owner?->name ?: '-' }}</dd>
                    <dt>{{ __('Last contacted') }}</dt><dd>{{ $contact->last_contacted_at?->diffForHumans() ?: '-' }}</dd>
                    <dt>{{ __('Tags') }}</dt>
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
                    @php
                        $dealStatusVariant = match($deal->status) { 'won' => 'success', 'lost' => 'danger', default => 'primary' };
                    @endphp
                    <div class="crm-list-item">
                        <a href="{{ route('crm.deals.show', $deal) }}">{{ $deal->title }}</a>
                        <span>
                            <x-admin-panel::badge :variant="$dealStatusVariant" size="sm">{{ $crmFormat->status($deal->status) }}</x-admin-panel::badge>
                            {{ number_format((float) $deal->value, 2) }} {{ $deal->currency }}
                            {{ $deal->stage ? '/ '.$deal->stage->name : '' }}
                        </span>
                    </div>
                @empty
                    <div class="crm-empty-state">
                        <strong>{{ __('No deals yet.') }}</strong>
                    </div>
                @endforelse
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Tasks</x-slot:header>
                @forelse($openTasks as $task)
                    @php
                        $priorityVariant = match($task->priority) { 'high', 'urgent' => 'danger', 'normal' => 'warning', default => 'secondary' };
                    @endphp
                    <div class="crm-list-item">
                        <a href="{{ route('crm.tasks.show', $task) }}">{{ $task->title }}</a>
                        <span>
                            <x-admin-panel::badge :variant="$priorityVariant" size="sm">{{ $crmFormat->status($task->priority) }}</x-admin-panel::badge>
                            {{ $task->due_at?->diffForHumans() ?: __('No due date') }}
                        </span>
                    </div>
                @empty
                    <div class="crm-empty-state">
                        <strong>{{ __('No open tasks.') }}</strong>
                    </div>
                @endforelse
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Quotes</x-slot:header>
                @forelse($contact->quotes->sortByDesc('created_at') as $quote)
                    @php
                        $quoteStatusVariant = match($quote->status) { 'accepted' => 'success', 'rejected' => 'danger', 'expired' => 'warning', 'sent' => 'info', default => 'secondary' };
                    @endphp
                    <div class="crm-list-item">
                        <a href="{{ route('crm.quotes.show', $quote) }}">{{ $quote->quote_number }}</a>
                        <span>
                            <x-admin-panel::badge :variant="$quoteStatusVariant" size="sm">{{ $crmFormat->status($quote->status) }}</x-admin-panel::badge>
                            {{ number_format((float) $quote->grand_total, 2) }} {{ $quote->currency }}
                        </span>
                    </div>
                @empty
                    <div class="crm-empty-state">
                        <strong>{{ __('No quotes yet.') }}</strong>
                    </div>
                @endforelse
            </x-admin-panel::card>
        </div>

        <x-admin-panel::card>
            <x-slot:header>Timeline</x-slot:header>
            @include('crm::admin.partials._timeline')
        </x-admin-panel::card>
    </section>
@endsection
