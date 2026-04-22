@extends('admin-panel::layouts.app')

@section('title', 'Quotes')
@section('page-title', 'Quotes')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
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

        <x-admin-panel::card>
            <form method="GET" action="{{ route('crm.quotes.index') }}" class="crm-filter-grid">
                <x-admin-panel::input name="search" label="Search" :value="$filters['search']" placeholder="Quote, company, contact or deal" />
                <x-admin-panel::select name="status" label="Status" :options="$statuses" :selected="$filters['status']" placeholder="All statuses" />
                <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$filters['owner_id']" placeholder="All owners" />
                <x-admin-panel::select name="tag_id" label="Tag" :options="$tags" :selected="$filters['tag_id']" placeholder="All tags" />
                <x-admin-panel::input name="valid_from" label="Valid From" type="date" :value="$filters['valid_from']" />
                <x-admin-panel::input name="valid_to" label="Valid To" type="date" :value="$filters['valid_to']" />

                <div class="crm-filter-actions">
                    <x-admin-panel::button type="submit" icon="search">Apply</x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.quotes.index')" variant="ghost">Reset</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>

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
                ['label' => 'Actions', 'width' => '180px'],
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
                        <td>{{ $quote->currency }} {{ number_format((float) $quote->grand_total, 2) }}</td>
                        <td>{{ $quote->valid_until?->format('Y-m-d') ?: '-' }}</td>
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
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="crm-empty">No quotes found.</td>
                    </tr>
                @endforelse
            </x-admin-panel::table>

            <div class="crm-pagination">
                {{ $quotes->links() }}
            </div>
        </x-admin-panel::card>
    </section>
@endsection
