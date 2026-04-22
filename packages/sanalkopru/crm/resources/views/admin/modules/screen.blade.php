@extends('admin-panel::layouts.app')

@section('title', $title)
@section('page-title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="{{ $module }}" data-crm-screen="{{ $screen }}">
        <header class="crm-admin-header">
            <p class="crm-admin-eyebrow">CRM / {{ ucfirst($screen) }}</p>
            <h1>{{ $title }}</h1>
        </header>

        <x-admin-panel::card>
            <div class="crm-admin-card-body">
                <x-admin-panel::badge variant="primary">
                    Registered admin screen
                </x-admin-panel::badge>

                <strong>{{ $title }} {{ $screen }}</strong>
                @if ($record)
                    <p>Record reference: {{ $record }}</p>
                @endif
            </div>
        </x-admin-panel::card>
    </section>
@endsection
