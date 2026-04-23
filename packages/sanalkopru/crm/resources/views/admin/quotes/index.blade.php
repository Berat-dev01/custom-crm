@extends('admin-panel::layouts.app')

@section('title', 'Quotes')
@section('page-title', 'Quotes')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $activeFilterCount = collect($filters)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->count();
    @endphp

    <section class="crm-admin-page" data-crm-module="quotes">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM</p>
                <h1>Quotes</h1>
            </div>

            <div class="crm-admin-actions">
                @can('crm.quotes.export')
                    <x-admin-panel::button :href="route('crm.quotes.export', request()->query())" variant="outline" icon="download">
                        Export CSV
                    </x-admin-panel::button>
                @endcan
                @can('crm.quotes.create')
                    <x-admin-panel::button :href="route('crm.quotes.create')" icon="plus">
                        New Quote
                    </x-admin-panel::button>
                @endcan
            </div>
        </header>

        <div id="crm-quotes-list" class="admin-ajax-region" data-admin-ajax-list>
            <x-admin-panel::filter-shell :action="route('crm.quotes.index')" :reset-url="route('crm.quotes.index')" :active-count="$activeFilterCount">
                <x-slot:compact>
                    <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Quote, company, contact or deal" />
                    <x-admin-panel::select name="status" label="Status" :options="$statuses" :selected="$filters['status']" placeholder="All statuses" />
                    <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$filters['owner_id']" placeholder="All owners" />
                </x-slot:compact>

                <x-slot:advanced>
                    <x-admin-panel::select name="tag_id" label="Tag" :options="$tags" :selected="$filters['tag_id']" placeholder="All tags" />
                    <x-admin-panel::input name="valid_from" label="Valid From" type="date" :value="$filters['valid_from']" />
                    <x-admin-panel::input name="valid_to" label="Valid To" type="date" :value="$filters['valid_to']" />
                </x-slot:advanced>

                <x-slot:saved>
                    @include('crm::admin.partials.saved-filters', ['module' => 'quotes', 'savedFilters' => $savedFilters, 'filters' => $filters])
                </x-slot:saved>
            </x-admin-panel::filter-shell>

            <x-admin-panel::card>
                <x-slot:header>
                    Quotes
                </x-slot:header>

                <x-admin-panel::table :headers="[
                    ['label' => 'Quote'],
                    ['label' => 'Account'],
                    ['label' => 'Status'],
                    ['label' => 'Total'],
                    ['label' => 'Valid Until'],
                    ['label' => 'Owner'],
                    ['label' => 'Actions', 'width' => '240px'],
                ]">
                    @forelse($quotes as $quote)
                        <tr>
                            <td>
                                <strong>{{ $quote->quote_number }}</strong>
                                <div class="crm-muted">{{ $quote->items_count }} items{{ $quote->deal ? ' / '.$quote->deal->title : '' }}</div>
                            </td>
                            <td>{{ $quote->company?->name ?: $quote->contact?->full_name ?: '-' }}</td>
                            <td>
                                <x-admin-panel::badge variant="{{ $quote->status === 'accepted' ? 'success' : ($quote->status === 'rejected' ? 'danger' : 'info') }}">
                                    {{ ucfirst($quote->status) }}
                                </x-admin-panel::badge>
                            </td>
                            <td>{{ $crmFormat->money($quote->grand_total, $quote->currency) }}</td>
                            <td>{{ $crmFormat->date($quote->valid_until) }}</td>
                            <td>{{ $quote->owner?->name ?: '-' }}</td>
                            <td>
                                <div class="crm-row-actions">
                                    <x-admin-panel::button :href="route('crm.quotes.show', $quote)" size="sm" variant="ghost" icon="eye" />
                                    @can('update', $quote)
                                        <x-admin-panel::button :href="route('crm.quotes.edit', $quote)" size="sm" variant="ghost" icon="pencil" />
                                    @endcan
                                    @can('export', $quote)
                                        <x-admin-panel::button :href="route('crm.quotes.download', $quote)" size="sm" variant="ghost" icon="download" />
                                    @endcan
                                    @can('delete', $quote)
                                        <form method="POST" action="{{ route('crm.quotes.destroy', $quote) }}" class="crm-inline-form" data-crm-confirm="Delete this quote?">
                                            @csrf
                                            @method('DELETE')
                                            <x-admin-panel::button type="submit" size="sm" variant="danger" icon="trash-2" />
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                @include('crm::admin.partials.empty-state', [
                                    'title' => 'No quotes found.',
                                    'body' => 'Create a quote from a deal or start one manually.',
                                    'actionUrl' => route('crm.quotes.create'),
                                    'actionLabel' => 'New Quote',
                                    'actionPermission' => 'crm.quotes.create',
                                ])
                            </td>
                        </tr>
                    @endforelse
                </x-admin-panel::table>

                <x-admin-panel::pagination :paginator="$quotes" class="crm-pagination" />
            </x-admin-panel::card>
        </div>
    </section>
@endsection
