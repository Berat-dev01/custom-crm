@extends('admin-panel::layouts.app')

@section('title', __('Contacts'))
@section('page-title', __('Contacts'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $activeFilterCount = collect($filters)
            ->except(['sort', 'direction'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->count();
        $tableHeaders = [
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input" aria-label="'.e(__('Select all contacts')).'">'), 'width' => '36px'],
            ['label' => __('Name')],
            ['label' => __('Company')],
            ['label' => __('Stage')],
            ['label' => __('Owner')],
            ['label' => __('Activity')],
            ['label' => __('Actions'), 'width' => '220px'],
        ];
    @endphp

    <section class="crm-admin-page" data-crm-module="contacts">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM') }}</p>
                <h1>{{ __('Contacts') }}</h1>
            </div>

            <div class="crm-admin-actions">
                @can('crm.contacts.import')
                    <x-admin-panel::button :href="route('crm.contacts.template')" variant="ghost" icon="download">
                        {{ __('Template') }}
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.contacts.import')" variant="outline" icon="upload">
                        {{ __('Import') }}
                    </x-admin-panel::button>
                @endcan
                @can('crm.contacts.export')
                    <x-admin-panel::export-button
                        :url="route('crm.contacts.export')"
                        :columns="$exportColumns"
                        :formats="$exportFormats"
                        module="contacts"
                    />
                @endcan
                @can('crm.contacts.create')
                    <x-admin-panel::button :href="route('crm.contacts.create')" icon="plus">
                        {{ __('New Contact') }}
                    </x-admin-panel::button>
                @endcan
            </div>
        </header>

        <div id="crm-contacts-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('crm.contacts.index')" :reset-url="route('crm.contacts.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Name, email or phone" />
                    <x-admin-panel::select name="lifecycle_stage" label="Lifecycle" :options="$lifecycleStages" :selected="$filters['lifecycle_stage']" placeholder="All stages" />
                    <x-admin-panel::select name="company_id" label="Company" :options="$companies" :selected="$filters['company_id']" placeholder="All companies" />
                </x-slot:compact>

                <x-slot:advanced>
                    <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$filters['owner_id']" placeholder="All owners" />
                    <x-admin-panel::select name="tag_id" label="Tag" :options="$tags" :selected="$filters['tag_id']" placeholder="All tags" />
                    <x-admin-panel::select
                        name="sort"
                        label="Sort"
                        :selected="$filters['sort']"
                        :options="[
                            'created_at' => __('Created'),
                            'full_name' => __('Name'),
                            'email' => __('Email'),
                            'last_contacted_at' => __('Last contacted'),
                        ]"
                    />
                </x-slot:advanced>
                <x-slot:saved>
                    @include('crm::admin.partials.saved-filters', ['module' => 'contacts', 'savedFilters' => $savedFilters, 'filters' => $filters])
                </x-slot:saved>
            </x-admin-panel::filter-shell>

            <form id="crm-contact-bulk" method="POST" action="{{ route('crm.contacts.bulk-tags') }}">
                @csrf

                <x-admin-panel::bulk-actions form="crm-contact-bulk" checkbox-selector=".crm-contact-selector" label="contacts">
                    @can('crm.contacts.update')
                        <div
                            data-admin-select
                            data-admin-select-placeholder="{{ __('Select tags') }}"
                            data-admin-select-searchable="1"
                            data-admin-select-clearable="1"
                        >
                            <select name="tag_ids[]" class="form-control" multiple form="crm-contact-bulk" data-admin-select-native>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="taggable_type" value="contact">
                        <input type="hidden" name="mode" value="detach">
                        <x-admin-panel::button type="submit" size="sm" variant="outline" icon="tag" form="crm-contact-bulk">
                            {{ __('Assign Tags') }}
                        </x-admin-panel::button>
                        <x-admin-panel::button type="submit" size="sm" variant="ghost" icon="tag" form="crm-contact-bulk" formaction="{{ route('crm.tags.bulk') }}">
                            {{ __('Remove Tags') }}
                        </x-admin-panel::button>
                    @endcan
                    @can('crm.contacts.delete')
                        <x-admin-panel::button
                            type="submit"
                            size="sm"
                            variant="danger"
                            icon="trash-2"
                            form="crm-contact-bulk"
                            formaction="{{ route('crm.contacts.bulk-delete') }}"
                            name="_method"
                            value="DELETE"
                            data-crm-confirm="{{ __('Delete selected contacts?') }}"
                        >
                            {{ __('Delete Selected') }}
                        </x-admin-panel::button>
                    @endcan
                </x-admin-panel::bulk-actions>

                <x-admin-panel::card>
                    <x-slot:header>{{ __('Contacts') }}</x-slot:header>

                    <x-admin-panel::table :headers="$tableHeaders">
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
                                    <div class="crm-muted">{{ $contact->email ?: __('No email') }}{{ $contact->phone ? ' / '.$contact->phone : '' }}</div>
                                </td>
                                <td>{{ $contact->company?->name ?: '-' }}</td>
                                <td>
                                    <x-admin-panel::badge variant="info">{{ ucfirst($contact->lifecycle_stage) }}</x-admin-panel::badge>
                                </td>
                                <td>{{ $contact->owner?->name ?: '-' }}</td>
                                <td>
                                    <span class="crm-muted">
                                        {{ __(':deals deals, :tasks tasks, :quotes quotes', ['deals' => $contact->deals_count, 'tasks' => $contact->tasks_count, 'quotes' => $contact->quotes_count]) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="crm-row-actions">
                                        <x-admin-panel::button :href="route('crm.contacts.show', $contact)" size="sm" variant="ghost" icon="eye" />
                                        @can('update', $contact)
                                            <x-admin-panel::button :href="route('crm.contacts.edit', $contact)" size="sm" variant="ghost" icon="pencil" />
                                        @endcan
                                        @can('delete', $contact)
                                            <x-admin-panel::button
                                                type="submit"
                                                form="crm-contact-delete-{{ $contact->id }}"
                                                size="sm"
                                                variant="danger"
                                                icon="trash-2"
                                                data-crm-confirm="{{ __('Delete this contact?') }}"
                                            />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    @include('crm::admin.partials.empty-state', [
                                        'title' => __('No contacts found.'),
                                        'body' => __('Create a contact or adjust filters to continue the sales workflow.'),
                                        'actionUrl' => route('crm.contacts.create'),
                                        'actionLabel' => __('New Contact'),
                                        'actionPermission' => 'crm.contacts.create',
                                    ])
                                </td>
                            </tr>
                        @endforelse
                    </x-admin-panel::table>

                    <x-admin-panel::pagination :paginator="$contacts" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>

        @foreach($contacts as $contact)
            @can('delete', $contact)
                <form id="crm-contact-delete-{{ $contact->id }}" method="POST" action="{{ route('crm.contacts.destroy', $contact) }}" class="crm-hidden-form">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
        @endforeach
    </section>
@endsection
