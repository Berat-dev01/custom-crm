@extends('admin-panel::layouts.app')

@section('title', 'Companies')
@section('page-title', 'Companies')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="companies">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM</p>
                <h1>Companies</h1>
            </div>

            <div class="crm-admin-actions">
                @can('crm.companies.import')
                    <x-admin-panel::button :href="route('crm.companies.template')" variant="ghost" icon="download">
                        Template
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.companies.import')" variant="outline" icon="upload">
                        Import
                    </x-admin-panel::button>
                @endcan
                @can('crm.companies.export')
                    <x-admin-panel::button :href="route('crm.companies.export', request()->query())" variant="outline" icon="download">
                        Export CSV
                    </x-admin-panel::button>
                @endcan
                @can('crm.companies.create')
                    <x-admin-panel::button :href="route('crm.companies.create')" icon="plus">
                        New Company
                    </x-admin-panel::button>
                @endcan
            </div>
        </header>

        <x-admin-panel::card>
            <form method="GET" action="{{ route('crm.companies.index') }}" class="crm-filter-grid">
                <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Name, email, phone or tax number" />
                <x-admin-panel::select name="sector" label="Sector" :options="$sectors" :selected="$filters['sector']" placeholder="All sectors" />
                <x-admin-panel::input name="city" label="City" :value="$filters['city']" placeholder="City" />
                <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$filters['owner_id']" placeholder="All owners" />
                <x-admin-panel::select name="tag_id" label="Tag" :options="$tags" :selected="$filters['tag_id']" placeholder="All tags" />
                <x-admin-panel::select
                    name="sort"
                    label="Sort"
                    :selected="$filters['sort']"
                    :options="[
                        'created_at' => 'Created',
                        'name' => 'Name',
                        'sector' => 'Sector',
                        'city' => 'City',
                    ]"
                />

                <div class="crm-filter-actions">
                    <x-admin-panel::button type="submit" icon="search">Apply</x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.companies.index')" variant="ghost">Reset</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

        @include('crm::admin.partials.saved-filters', ['module' => 'companies', 'savedFilters' => $savedFilters, 'filters' => $filters])

        <x-admin-panel::card>
            <x-slot:header>
                Companies
            </x-slot:header>

            <x-admin-panel::table :headers="[
                ['label' => 'Name'],
                ['label' => 'Sector'],
                ['label' => 'Location'],
                ['label' => 'Owner'],
                ['label' => 'CRM Links'],
                ['label' => 'Actions', 'width' => '160px'],
            ]">
                @forelse($companies as $company)
                    <tr>
                        <td>
                            <strong>{{ $company->name }}</strong>
                            <div class="crm-muted">{{ $company->email ?: 'No email' }}{{ $company->phone ? ' / '.$company->phone : '' }}</div>
                        </td>
                        <td>{{ $company->sector ?: '-' }}</td>
                        <td>{{ collect([$company->city, $company->country])->filter()->implode(', ') ?: '-' }}</td>
                        <td>{{ $company->owner?->name ?: '-' }}</td>
                        <td>
                            <span class="crm-muted">
                                {{ $company->contacts_count }} contacts,
                                {{ $company->deals_count }} deals,
                                {{ $company->quotes_count }} quotes
                            </span>
                        </td>
                        <td>
                            <div class="crm-row-actions">
                                <x-admin-panel::button :href="route('crm.companies.show', $company)" size="sm" variant="ghost" icon="eye" />
                                @can('update', $company)
                                    <x-admin-panel::button :href="route('crm.companies.edit', $company)" size="sm" variant="ghost" icon="pencil" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="crm-empty">No companies found.</td>
                    </tr>
                @endforelse
            </x-admin-panel::table>

            <div class="crm-pagination">
                {{ $companies->links() }}
            </div>
        </x-admin-panel::card>
    </section>
@endsection
