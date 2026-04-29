@extends('crm::layouts.app')

@section('title', __($title))
@section('page-title', __($title))


@section('content')
    <section class="crm-admin-page" data-crm-module="{{ $module }}" data-crm-screen="{{ $screen }}">
        <header class="crm-admin-header">
            <p class="crm-admin-eyebrow">CRM / {{ __(ucfirst($screen)) }}</p>
            <h1>{{ __($title) }}</h1>
        </header>

        <x-admin-panel::card>
            <div class="crm-admin-card-body">
                <x-admin-panel::badge variant="primary">
                    {{ __('Registered admin screen') }}
                </x-admin-panel::badge>

                <strong>{{ __($title) }} {{ __(ucfirst($screen)) }}</strong>
                @if ($record)
                    <p>{{ __('Record reference: :record', ['record' => $record]) }}</p>
                @endif
            </div>
        </x-admin-panel::card>
    </section>
@endsection
