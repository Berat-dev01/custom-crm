@extends('crm::layouts.app')

@section('title', __('Search CRM'))
@section('page-title', __('Search CRM'))


@section('content')
    <section class="crm-admin-page" data-crm-module="search">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header">
            <p class="crm-admin-eyebrow">{{ __('CRM') }}</p>
            <h1>{{ __('Search CRM') }}</h1>
            <p class="crm-muted">{{ $term ? __('Results for ":term"', ['term' => $term]) : __('Type at least two characters to search across CRM records.') }}</p>
        </header>

        @php($totalResults = collect($groups)->sum('total'))

        @if($term && $totalResults === 0)
            <x-admin-panel::card>
                @include('crm::admin.partials.empty-state', [
                    'title' => __('No CRM records matched.'),
                    'body' => __('Try a company name, contact email, deal title or quote number.'),
                    'actionUrl' => route('crm.contacts.create'),
                    'actionLabel' => __('New Contact'),
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
