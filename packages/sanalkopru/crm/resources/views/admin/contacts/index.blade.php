@extends('admin-panel::layouts.app')

@section('title', 'Contacts')
@section('page-title', 'Contacts')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="contacts">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM</p>
                <h1>Contacts</h1>
            </div>

            <div class="crm-admin-actions">
                @can('crm.contacts.import')
                    <x-admin-panel::button :href="route('crm.contacts.template')" variant="ghost" icon="download">
                        Template
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.contacts.import')" variant="outline" icon="upload">
                        Import
                    </x-admin-panel::button>
                @endcan
                @can('crm.contacts.export')
                    <x-admin-panel::button :href="route('crm.contacts.export', request()->query())" variant="outline" icon="download">
                        Export CSV
                    </x-admin-panel::button>
                @endcan
                @can('crm.contacts.create')
                    <x-admin-panel::button :href="route('crm.contacts.create')" icon="plus">
                        New Contact
                    </x-admin-panel::button>
                @endcan
            </div>
        </header>

        <x-admin-panel::card>
            <form method="GET" action="{{ route('crm.contacts.index') }}" class="crm-filter-grid">
                <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Name, email or phone" />
                <x-admin-panel::select name="lifecycle_stage" label="Lifecycle" :options="$lifecycleStages" :selected="$filters['lifecycle_stage']" placeholder="All stages" />
                <x-admin-panel::select name="company_id" label="Company" :options="$companies" :selected="$filters['company_id']" placeholder="All companies" />
                <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$filters['owner_id']" placeholder="All owners" />
                <x-admin-panel::select name="tag_id" label="Tag" :options="$tags" :selected="$filters['tag_id']" placeholder="All tags" />
                <x-admin-panel::select
                    name="sort"
                    label="Sort"
                    :selected="$filters['sort']"
                    :options="[
                        'created_at' => 'Created',
                        'full_name' => 'Name',
                        'email' => 'Email',
                        'last_contacted_at' => 'Last contacted',
                    ]"
                />

                <div class="crm-filter-actions">
                    <x-admin-panel::button type="submit" icon="search">Apply</x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.contacts.index')" variant="ghost">Reset</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        @include('crm::admin.partials.saved-filters', ['module' => 'contacts', 'savedFilters' => $savedFilters, 'filters' => $filters])

        <form id="crm-contact-bulk" method="POST" action="{{ route('crm.contacts.bulk-tags') }}">
            @csrf

        <x-admin-panel::card>
            <x-slot:header>
                Contacts
            </x-slot:header>

            <x-slot:headerActions>
                @can('crm.contacts.update')
                    <div class="crm-inline-form">
                        <select name="tag_ids[]" class="form-control" multiple>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                        <x-admin-panel::button type="submit" size="sm" variant="outline" icon="tag">
                            Assign Tags
                        </x-admin-panel::button>
                        <x-admin-panel::button type="submit" size="sm" variant="ghost" icon="tag" formaction="{{ route('crm.tags.bulk') }}">
                            Remove Tags
                        </x-admin-panel::button>
                        <input type="hidden" name="taggable_type" value="contact">
                        <input type="hidden" name="mode" value="detach">
                    </div>
                @endcan
                @can('crm.contacts.delete')
                    <x-admin-panel::button
                        type="submit"
                        size="sm"
                        variant="ghost"
                        icon="trash-2"
                        formaction="{{ route('crm.contacts.bulk-delete') }}"
                        name="_method"
                        value="DELETE"
                    >
                        Delete Selected
                    </x-admin-panel::button>
                @endcan
            </x-slot:headerActions>

            <x-admin-panel::table :headers="[
                ['label' => '', 'width' => '36px'],
                ['label' => 'Name'],
                ['label' => 'Company'],
                ['label' => 'Stage'],
                ['label' => 'Owner'],
                ['label' => 'Activity'],
                ['label' => 'Actions', 'width' => '180px'],
            ]">
                @forelse($contacts as $contact)
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                name="contact_ids[]"
                                value="{{ $contact->id }}"
                                class="form-check-input crm-contact-selector"
                            >
                        </td>
                        <td>
                            <strong>{{ $contact->full_name }}</strong>
                            <div class="crm-muted">{{ $contact->email ?: 'No email' }}{{ $contact->phone ? ' / '.$contact->phone : '' }}</div>
                        </td>
                        <td>{{ $contact->company?->name ?: '-' }}</td>
                        <td>
                            <x-admin-panel::badge variant="info">{{ ucfirst($contact->lifecycle_stage) }}</x-admin-panel::badge>
                        </td>
                        <td>{{ $contact->owner?->name ?: '-' }}</td>
                        <td>
                            <span class="crm-muted">
                                {{ $contact->deals_count }} deals,
                                {{ $contact->tasks_count }} tasks,
                                {{ $contact->quotes_count }} quotes
                            </span>
                        </td>
                        <td>
                            <div class="crm-row-actions">
                                <x-admin-panel::button :href="route('crm.contacts.show', $contact)" size="sm" variant="ghost" icon="eye" />
                                @can('update', $contact)
                                    <x-admin-panel::button :href="route('crm.contacts.edit', $contact)" size="sm" variant="ghost" icon="pencil" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="crm-empty">No contacts found.</td>
                    </tr>
                @endforelse
            </x-admin-panel::table>

            <div class="crm-pagination">
                {{ $contacts->links() }}
            </div>
        </x-admin-panel::card>
        </form>
    </section>
@endsection
