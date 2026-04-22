@extends('admin-panel::layouts.app')

@section('title', 'CRM Dashboard')
@section('page-title', 'CRM Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="dashboard">
        <header class="crm-admin-header">
            <p class="crm-admin-eyebrow">CRM Engine</p>
            <h1>Dashboard</h1>
        </header>

        <div class="crm-admin-grid">
            <x-admin-panel::stat-card
                label="Routing"
                value="Package dashboard route is ready."
                icon="route"
                variant="primary"
            />

            <x-admin-panel::stat-card
                label="Modules"
                value="Admin navigation skeleton is ready."
                icon="layout-dashboard"
                variant="info"
            />
        </div>
    </section>
@endsection
