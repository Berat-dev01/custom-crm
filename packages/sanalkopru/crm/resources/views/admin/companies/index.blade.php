@extends('admin-panel::layouts.app')

@section('title', __('Companies'))
@section('page-title', __('Companies'))

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
            ['label' => new \Illuminate\Support\HtmlString('<input type="checkbox" data-admin-bulk-toggle-all class="form-check-input" aria-label="'.e(__('Select all companies')).'">'), 'width' => '36px'],
            ['label' => __('Name')],
            ['label' => __('Sector')],
            ['label' => __('Location')],
            ['label' => __('Owner')],
            ['label' => __('CRM Links')],
            ['label' => __('Actions'), 'width' => '220px'],
        ];
    @endphp

    <section class="crm-admin-page" data-crm-module="companies">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM') }}</p>
                <h1>{{ __('Companies') }}</h1>
            </div>

            <div class="crm-admin-actions">
                @can('crm.companies.import')
                    <x-admin-panel::button :href="route('crm.companies.template')" variant="ghost" icon="download">
                        {{ __('Template') }}
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.companies.import')" variant="outline" icon="upload">
                        {{ __('Import') }}
                    </x-admin-panel::button>
                @endcan
                @can('crm.companies.export')
                    <x-admin-panel::export-button
                        :url="route('crm.companies.export')"
                        :columns="$exportColumns"
                        :formats="$exportFormats"
                        module="companies"
                    />
                @endcan
                @can('crm.companies.create')
                    <x-admin-panel::button :href="route('crm.companies.create')" icon="plus">
                        {{ __('New Company') }}
                    </x-admin-panel::button>
                @endcan
            </div>
        </header>

        <div id="crm-companies-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('crm.companies.index')" :reset-url="route('crm.companies.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Name, email, phone or tax number" />
                    <x-admin-panel::select name="sector" label="Sector" :options="$sectors" :selected="$filters['sector']" placeholder="All sectors" />
                    <x-admin-panel::input name="city" label="City" :value="$filters['city']" placeholder="City" />
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
                            'name' => __('Name'),
                            'sector' => __('Sector'),
                            'city' => __('City'),
                        ]"
                    />
                </x-slot:advanced>

                <x-slot:saved>
                    @include('crm::admin.partials.saved-filters', ['module' => 'companies', 'savedFilters' => $savedFilters, 'filters' => $filters])
                </x-slot:saved>
            </x-admin-panel::filter-shell>

            <form id="crm-company-bulk" method="POST" action="{{ route('crm.companies.bulk-delete') }}">
                @csrf
                @method('DELETE')

                <x-admin-panel::bulk-actions form="crm-company-bulk" checkbox-selector=".crm-company-selector" label="companies">
                    @can('crm.companies.delete')
                        <x-admin-panel::button
                            type="submit"
                            size="sm"
                            variant="danger"
                            icon="trash-2"
                            form="crm-company-bulk"
                            data-crm-confirm="{{ __('Delete selected companies?') }}"
                        >
                            {{ __('Delete Selected') }}
                        </x-admin-panel::button>
                    @endcan
                </x-admin-panel::bulk-actions>

                <x-admin-panel::card>
                    <x-slot:header>{{ __('Companies') }}</x-slot:header>

                    <x-admin-panel::table :headers="$tableHeaders">
                    @forelse($companies as $company)
                        <tr>
                            <td>
                                <input
                                    type="checkbox"
                                    name="record_ids[]"
                                    value="{{ $company->id }}"
                                    class="form-check-input crm-company-selector"
                                >
                            </td>
                            <td>
                                <strong>{{ $company->name }}</strong>
                                <div class="crm-muted">{{ $company->email ?: __('No email') }}{{ $company->phone ? ' / '.$company->phone : '' }}</div>
                            </td>
                            <td>{{ $company->sector ?: '-' }}</td>
                            <td>{{ collect([$company->city, $company->country])->filter()->implode(', ') ?: '-' }}</td>
                            <td>{{ $company->owner?->name ?: '-' }}</td>
                            <td>
                                <span class="crm-muted">
                                    {{ __(':contacts contacts, :deals deals, :quotes quotes', ['contacts' => $company->contacts_count, 'deals' => $company->deals_count, 'quotes' => $company->quotes_count]) }}
                                </span>
                            </td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('crm.companies.show', $company)" size="sm" variant="ghost" icon="eye" />
                                    @can('update', $company)
                                        <x-admin-panel::button :href="route('crm.companies.edit', $company)" size="sm" variant="ghost" icon="pencil" />
                                    @endcan
                                    @can('delete', $company)
                                        <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" form="crm-company-delete-{{ $company->id }}" data-crm-confirm="{{ __('Delete this company?') }}" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                @include('crm::admin.partials.empty-state', [
                                    'title' => __('No companies found.'),
                                    'body' => __('Add an account record or reset filters to see existing companies.'),
                                    'actionUrl' => route('crm.companies.create'),
                                    'actionLabel' => __('New Company'),
                                    'actionPermission' => 'crm.companies.create',
                                ])
                            </td>
                        </tr>
                    @endforelse
                    </x-admin-panel::table>

                    <x-admin-panel::pagination :paginator="$companies" class="crm-pagination" />
                </x-admin-panel::card>
            </form>
        </div>

        @foreach($companies as $company)
            @can('delete', $company)
                <form id="crm-company-delete-{{ $company->id }}" method="POST" action="{{ route('crm.companies.destroy', $company) }}" class="crm-hidden-form">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
        @endforeach
    </section>
@endsection
