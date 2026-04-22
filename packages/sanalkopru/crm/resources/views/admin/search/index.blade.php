@extends('admin-panel::layouts.app')

@section('title', 'Search CRM')
@section('page-title', 'Search CRM')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="search">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header">
            <p class="crm-admin-eyebrow">CRM</p>
            <h1>Search CRM</h1>
            <p class="crm-muted">{{ $term ? 'Results for "'.$term.'"' : 'Type at least two characters to search across CRM records.' }}</p>
        </header>

        @php($totalResults = collect($groups)->sum('total'))

        @if($term && $totalResults === 0)
            <x-admin-panel::card>
                @include('crm::admin.partials.empty-state', [
                    'title' => 'No CRM records matched.',
                    'body' => 'Try a company name, contact email, deal title or quote number.',
                    'actionUrl' => route('crm.contacts.create'),
                    'actionLabel' => 'New Contact',
                ])
            </x-admin-panel::card>
        @endif

        @foreach($groups as $group)
            @can($group['permission'])
                @if(count($group['items']) > 0)
                    <x-admin-panel::card>
                        <x-slot:header>{{ $group['label'] }}</x-slot:header>
                        <div class="crm-search-results">
                            @foreach($group['items'] as $item)
                                <a href="{{ $item['url'] }}" class="crm-search-result">
                                    <span>
                                        <strong>{{ $item['title'] }}</strong>
                                        <small>{{ $item['subtitle'] }}</small>
                                    </span>
                                    <span class="crm-search-badge">{{ $item['badge'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </x-admin-panel::card>
                @endif
            @endcan
        @endforeach
    </section>
@endsection
