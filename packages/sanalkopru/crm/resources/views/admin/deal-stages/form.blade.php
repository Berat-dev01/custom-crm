@extends('crm::layouts.app')

@section('title', $stage->exists ? __('Edit Deal Stage') : __('New Deal Stage'))
@section('page-title', $stage->exists ? __('Edit Deal Stage') : __('New Deal Stage'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="deal-stages">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Deal Stages') }}</p>
                <h1>{{ $stage->exists ? __('Edit Deal Stage') : __('New Deal Stage') }}</h1>
            </div>

            <x-admin-panel::button :href="route('crm.deal-stages.index')" variant="ghost" icon="arrow-left">
                {{ __('Back') }}
            </x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <form
                method="POST"
                action="{{ $stage->exists ? route('crm.deal-stages.update', $stage) : route('crm.deal-stages.store') }}"
                class="crm-form-grid"
            >
                @csrf
                @if($stage->exists)
                    @method('PUT')
                @endif

                <x-admin-panel::input name="name" label="Name" :value="$stage->name" required />
                <x-admin-panel::input name="slug" label="Slug" :value="$stage->slug" placeholder="Generated from name when empty" />
                <x-admin-panel::input name="color" label="Color" type="color" :value="$stage->color ?: '#64748b'" required />
                <x-admin-panel::input name="position" label="Position" type="number" :value="$stage->position ?: 1" min="1" max="1000" required />
                <x-admin-panel::input name="probability" label="Probability" type="number" :value="$stage->probability ?? 0" min="0" max="100" required />

                <div class="form-group">
                    <label class="form-label">{{ __('Stage Type') }}</label>
                    <label class="crm-checkbox-row">
                        <input type="checkbox" name="is_won" value="1" @checked(old('is_won', $stage->is_won))>
                        <span>{{ __('Won stage') }}</span>
                    </label>
                    <label class="crm-checkbox-row">
                        <input type="checkbox" name="is_lost" value="1" @checked(old('is_lost', $stage->is_lost))>
                        <span>{{ __('Lost stage') }}</span>
                    </label>
                    @error('is_won')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                    @error('is_lost')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="crm-form-actions crm-span-2">
                    <x-admin-panel::button type="submit" icon="save">
                        {{ $stage->exists ? __('Save Stage') : __('Create Stage') }}
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.deal-stages.index')" variant="ghost">
                        {{ __('Cancel') }}
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>
    </section>
@endsection
