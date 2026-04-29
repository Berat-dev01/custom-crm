@extends('crm::layouts.app')

@section('title', $company->name)
@section('page-title', $company->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="companies">
        @include('crm::admin.partials.status')

        @if($errors->has('company'))
            <x-admin-panel::alert variant="danger">
                {{ $errors->first('company') }}
            </x-admin-panel::alert>
        @endif

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Companies') }}</p>
                <h1>{{ $company->name }}</h1>
                <p class="crm-muted">{{ $company->sector ?: __('No sector') }}{{ $company->city ? ' / '.$company->city : '' }}</p>
            </div>

            <div class="crm-admin-actions">
                @can('update', $company)
                    <x-admin-panel::button :href="route('crm.companies.edit', $company)" icon="pencil">
                        Edit
                    </x-admin-panel::button>
                @endcan
                @can('delete', $company)
                    <form method="POST" action="{{ route('crm.companies.destroy', $company) }}" data-crm-confirm="{{ __('Delete this company?') }}">
                        @csrf
                        @method('DELETE')
                        <x-admin-panel::button type="submit" variant="danger" icon="trash-2">
                            Delete
                        </x-admin-panel::button>
                    </form>
                @endcan
                <x-admin-panel::button :href="route('crm.companies.index')" variant="ghost" icon="arrow-left">
                    Back
                </x-admin-panel::button>
            </div>
        </header>

        <div class="crm-admin-grid">
            <x-admin-panel::stat-card label="Open Deal Value" :value="$crmFormat->money($openDealsValue)" icon="circle-dollar-sign" variant="success" />
            <x-admin-panel::stat-card label="Contacts" :value="$company->contacts->count()" icon="users" variant="primary" />
            <x-admin-panel::stat-card label="Open Deals" :value="$openDeals->count()" icon="kanban-square" variant="warning" />
            <x-admin-panel::stat-card label="Quotes" :value="$company->quotes->count()" icon="file-text" variant="info" />
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>Company Info</x-slot:header>
                <dl class="crm-detail-list">
                    <dt>{{ __('Email') }}</dt><dd>{{ $company->email ?: '-' }}</dd>
                    <dt>{{ __('Phone') }}</dt><dd>{{ $company->phone ?: '-' }}</dd>
                    <dt>{{ __('Website') }}</dt><dd>{{ $company->website ?: '-' }}</dd>
                    <dt>{{ __('Tax number') }}</dt><dd>{{ $company->tax_number ?: '-' }}</dd>
                    <dt>{{ __('Tax office') }}</dt><dd>{{ $company->tax_office ?: '-' }}</dd>
                    <dt>{{ __('Owner') }}</dt><dd>{{ $company->owner?->name ?: '-' }}</dd>
                    <dt>{{ __('Address') }}</dt>
                    <dd>{{ collect([$company->address_line_1, $company->address_line_2, $company->city, $company->state, $company->postal_code, $company->country])->filter()->implode(', ') ?: '-' }}</dd>
                    <dt>{{ __('Tags') }}</dt>
                    <dd>
                        @forelse($company->tags as $tag)
                            <x-admin-panel::badge variant="secondary">{{ $tag->name }}</x-admin-panel::badge>
                        @empty
                            -
                        @endforelse
                    </dd>
                </dl>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Attach Contacts</x-slot:header>
                @can('update', $company)
                    @if($availableContacts->isNotEmpty())
                        <form method="POST" action="{{ route('crm.companies.contacts.attach', $company) }}" class="crm-stack">
                            @csrf
                            <div
                                class="form-group"
                                data-admin-select
                                data-admin-select-placeholder="{{ __('Select contacts') }}"
                                data-admin-select-searchable="1"
                                data-admin-select-clearable="1"
                            >
                                <label class="form-label" for="contact_ids">{{ __('Available Contacts') }}</label>
                                <select id="contact_ids" name="contact_ids[]" class="form-control" multiple required data-admin-select-native>
                                    @foreach($availableContacts as $contact)
                                        <option value="{{ $contact->id }}">
                                            {{ $contact->full_name }}{{ $contact->email ? ' / '.$contact->email : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <x-admin-panel::button type="submit" icon="link">Attach Contacts</x-admin-panel::button>
                        </form>
                    @else
                        <p class="crm-muted">{{ __('No unassigned contacts are available.') }}</p>
                    @endif
                @endcan
            </x-admin-panel::card>
        </div>

        <div class="crm-three-column">
            <x-admin-panel::card>
                <x-slot:header>Contacts</x-slot:header>
                @forelse($company->contacts->sortBy('full_name') as $contact)
                    <div class="crm-list-item">
                        <a href="{{ route('crm.contacts.show', $contact) }}">{{ $contact->full_name }}</a>
                        <span>{{ $contact->title ? $contact->title.' · ' : '' }}{{ $contact->email ?: __('No email') }}</span>
                    </div>
                @empty
                    <div class="crm-empty-state">
                        <strong>{{ __('No contacts attached.') }}</strong>
                    </div>
                @endforelse
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Open Deals</x-slot:header>
                @forelse($openDeals as $deal)
                    <div class="crm-list-item">
                        <a href="{{ route('crm.deals.show', $deal) }}">{{ $deal->title }}</a>
                        <span>{{ number_format((float) $deal->value, 2) }} {{ $deal->currency }}{{ $deal->stage ? ' / '.$deal->stage->name : '' }}</span>
                    </div>
                @empty
                    <div class="crm-empty-state">
                        <strong>{{ __('No open deals.') }}</strong>
                    </div>
                @endforelse
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Quotes</x-slot:header>
                @forelse($company->quotes->sortByDesc('created_at') as $quote)
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

        <div class="crm-two-column">
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
                <x-slot:header>Activities</x-slot:header>
                @include('crm::admin.partials._timeline')
            </x-admin-panel::card>
        </div>
    </section>
@endsection
