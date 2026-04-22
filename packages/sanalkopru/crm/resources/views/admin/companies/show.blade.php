@extends('admin-panel::layouts.app')

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
                <p class="crm-admin-eyebrow">CRM / Companies</p>
                <h1>{{ $company->name }}</h1>
                <p class="crm-muted">{{ $company->sector ?: 'No sector' }}{{ $company->city ? ' / '.$company->city : '' }}</p>
            </div>

            <div class="crm-admin-actions">
                @can('update', $company)
                    <x-admin-panel::button :href="route('crm.companies.edit', $company)" icon="pencil">
                        Edit
                    </x-admin-panel::button>
                @endcan
                @can('delete', $company)
                    <form method="POST" action="{{ route('crm.companies.destroy', $company) }}">
                        @csrf
                        @method('DELETE')
                        <x-admin-panel::button type="submit" variant="ghost" icon="trash-2">
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
            <x-admin-panel::stat-card label="Open Deal Value" :value="number_format($openDealsValue, 2).' '.config('crm.money.default_currency')" icon="circle-dollar-sign" variant="success" />
            <x-admin-panel::stat-card label="Contacts" :value="$company->contacts->count()" icon="users" variant="primary" />
            <x-admin-panel::stat-card label="Open Deals" :value="$openDeals->count()" icon="kanban-square" variant="warning" />
            <x-admin-panel::stat-card label="Quotes" :value="$company->quotes->count()" icon="file-text" variant="info" />
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>Company Info</x-slot:header>
                <dl class="crm-detail-list">
                    <dt>Email</dt><dd>{{ $company->email ?: '-' }}</dd>
                    <dt>Phone</dt><dd>{{ $company->phone ?: '-' }}</dd>
                    <dt>Website</dt><dd>{{ $company->website ?: '-' }}</dd>
                    <dt>Tax number</dt><dd>{{ $company->tax_number ?: '-' }}</dd>
                    <dt>Tax office</dt><dd>{{ $company->tax_office ?: '-' }}</dd>
                    <dt>Owner</dt><dd>{{ $company->owner?->name ?: '-' }}</dd>
                    <dt>Address</dt>
                    <dd>{{ collect([$company->address_line_1, $company->address_line_2, $company->city, $company->state, $company->postal_code, $company->country])->filter()->implode(', ') ?: '-' }}</dd>
                    <dt>Tags</dt>
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
                            <div class="form-group">
                                <label class="form-label" for="contact_ids">Available Contacts</label>
                                <select id="contact_ids" name="contact_ids[]" class="form-control" multiple required>
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
                        <p class="crm-muted">No unassigned contacts are available.</p>
                    @endif
                @endcan
            </x-admin-panel::card>
        </div>

        <div class="crm-three-column">
            <x-admin-panel::card>
                <x-slot:header>Contacts</x-slot:header>
                @forelse($company->contacts->sortBy('full_name') as $contact)
                    <div class="crm-list-item">
                        <strong>{{ $contact->full_name }}</strong>
                        <span>{{ $contact->email ?: 'No email' }}</span>
                    </div>
                @empty
                    <p class="crm-muted">No contacts attached.</p>
                @endforelse
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Open Deals</x-slot:header>
                @forelse($openDeals as $deal)
                    <div class="crm-list-item">
                        <strong>{{ $deal->title }}</strong>
                        <span>{{ number_format((float) $deal->value, 2) }} {{ $deal->currency }} / {{ $deal->stage?->name }}</span>
                    </div>
                @empty
                    <p class="crm-muted">No open deals.</p>
                @endforelse
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>Quotes</x-slot:header>
                @forelse($company->quotes->sortByDesc('created_at') as $quote)
                    <div class="crm-list-item">
                        <strong><a href="{{ route('crm.quotes.show', $quote) }}">{{ $quote->quote_number }}</a></strong>
                        <span>{{ ucfirst($quote->status) }} / {{ number_format((float) $quote->grand_total, 2) }} {{ $quote->currency }}</span>
                    </div>
                @empty
                    <p class="crm-muted">No quotes yet.</p>
                @endforelse
            </x-admin-panel::card>
        </div>

        <div class="crm-two-column">
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
                <x-slot:header>Activities</x-slot:header>
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
        </div>
    </section>
@endsection
