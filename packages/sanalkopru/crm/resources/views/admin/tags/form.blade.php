@extends('admin-panel::layouts.app')

@section('title', $tag->exists ? __('Edit Tag') : __('New Tag'))
@section('page-title', $tag->exists ? __('Edit Tag') : __('New Tag'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="tags">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Tags') }}</p>
                <h1>{{ $tag->exists ? __('Edit Tag') : __('New Tag') }}</h1>
            </div>
            <x-admin-panel::button :href="route('crm.tags.index')" variant="ghost" icon="arrow-left">{{ __('Back') }}</x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <form
                method="POST"
                action="{{ $tag->exists ? route('crm.tags.update', $tag) : route('crm.tags.store') }}"
                class="crm-form-grid"
            >
                @csrf
                @if($tag->exists)
                    @method('PUT')
                @endif

                <x-admin-panel::input name="name" label="Name" :value="$tag->name" required />
                <x-admin-panel::input name="slug" label="Slug" :value="$tag->slug" placeholder="Generated from name when empty" />
                <x-admin-panel::input name="color" label="Color" type="color" :value="$tag->color ?: '#64748b'" required />

                <div class="crm-form-actions crm-span-2">
                    <x-admin-panel::button type="submit" icon="save">
                        {{ $tag->exists ? __('Save Tag') : __('Create Tag') }}
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.tags.index')" variant="ghost">{{ __('Cancel') }}</x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>
    </section>
@endsection
