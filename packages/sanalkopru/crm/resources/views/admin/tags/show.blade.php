@extends('crm::layouts.app')

@section('title', $tag->name)
@section('page-title', $tag->name)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="tags">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Tags') }}</p>
                <h1>
                    <span class="crm-color-swatch" style="background: {{ $tag->color }}"></span>
                    {{ $tag->name }}
                </h1>
            </div>
            <div class="crm-admin-actions">
                @can('update', $tag)
                    <x-admin-panel::button :href="route('crm.tags.edit', $tag)" icon="pencil">Edit</x-admin-panel::button>
                @endcan
                <x-admin-panel::button :href="route('crm.tags.index')" variant="ghost" icon="arrow-left">Back</x-admin-panel::button>
            </div>
        </header>

        <div class="crm-admin-grid">
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">{{ __('Contacts') }}</span>
                <strong>{{ $tag->contacts_count }}</strong>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">{{ __('Companies') }}</span>
                <strong>{{ $tag->companies_count }}</strong>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">{{ __('Deals') }}</span>
                <strong>{{ $tag->deals_count }}</strong>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">{{ __('Quotes') }}</span>
                <strong>{{ $tag->quotes_count }}</strong>
            </div>
        </div>

        <x-admin-panel::card>
            <x-slot:header>
                Tag Detail
            </x-slot:header>

            <dl class="crm-detail-list">
                <dt>{{ __('Slug') }}</dt>
                <dd>{{ $tag->slug }}</dd>
                <dt>{{ __('Color') }}</dt>
                <dd>{{ $tag->color }}</dd>
            </dl>
        </x-admin-panel::card>
    </section>
@endsection
