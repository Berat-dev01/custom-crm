@extends('crm::layouts.app')

@section('title', __('CRM Settings'))
@section('page-title', __('CRM Settings'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="settings">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header">
            <p class="crm-admin-eyebrow">{{ __('CRM / Settings') }}</p>
            <h1>{{ __('CRM Settings') }}</h1>
            <p class="crm-muted">{{ __('Branding, quote defaults, notifications and AI runtime options.') }}</p>
        </header>

        <form method="POST" action="{{ route('crm.settings.update') }}" enctype="multipart/form-data" class="crm-stack">
            @csrf
            @method('PUT')

            <x-admin-panel::card>
                <x-slot:header>{{ __('Company Branding') }}</x-slot:header>

                <div class="crm-form-grid">
                    <x-admin-panel::input name="company_name" label="Company Name" :value="old('company_name', $settings['company_name'])" required />
                    <x-admin-panel::input name="company_email" label="Company Email" type="email" :value="old('company_email', $settings['company_email'])" />
                    <x-admin-panel::input name="company_phone" label="Company Phone" :value="old('company_phone', $settings['company_phone'])" />
                    <x-admin-panel::input name="tax_number" label="Tax Number" :value="old('tax_number', $settings['tax_number'])" />
                    <x-admin-panel::input name="tax_office" label="Tax Office" :value="old('tax_office', $settings['tax_office'])" />
                    <x-admin-panel::input name="company_logo" label="Company Logo" type="file" accept="image/png,image/jpeg,image/webp" />
                    <x-admin-panel::textarea name="company_address" label="Company Address" class="crm-span-2" rows="3" :value="old('company_address', $settings['company_address'])" />
                </div>

                @if($logoUrl)
                    <div class="crm-settings-logo-preview">
                        <img src="{{ $logoUrl }}" alt="{{ $settings['company_name'] }}">
                        <span>{{ __('Current quote logo') }}</span>
                    </div>
                @endif
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>{{ __('Quote Defaults') }}</x-slot:header>

                <div class="crm-form-grid">
                    <x-admin-panel::select name="default_currency" label="Default Currency" :options="$currencies" :selected="old('default_currency', $settings['default_currency'])" required />
                    <x-admin-panel::input name="default_tax_rate" label="Default Tax Rate" type="number" min="0" max="100" step="0.01" :value="old('default_tax_rate', $settings['default_tax_rate'])" required />
                    <x-admin-panel::input name="quote_prefix" label="Quote Prefix" :value="old('quote_prefix', $settings['quote_prefix'])" required />
                    <x-admin-panel::textarea name="quote_terms" label="Default Quote Terms" class="crm-span-2" rows="5" :value="old('quote_terms', $settings['quote_terms'])" />
                </div>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>{{ __('Notifications') }}</x-slot:header>

                <div class="crm-settings-toggles">
                    <label class="crm-checkbox-row">
                        <input type="hidden" name="notify_task_reminders" value="0">
                        <input type="checkbox" name="notify_task_reminders" value="1" @checked(old('notify_task_reminders', $settings['notify_task_reminders']))>
                        <span>{{ __('Send task reminder notifications') }}</span>
                    </label>
                    <label class="crm-checkbox-row">
                        <input type="hidden" name="notify_task_assignments" value="0">
                        <input type="checkbox" name="notify_task_assignments" value="1" @checked(old('notify_task_assignments', $settings['notify_task_assignments']))>
                        <span>{{ __('Notify task assignment and reassignment') }}</span>
                    </label>
                    <label class="crm-checkbox-row">
                        <input type="hidden" name="notify_quote_status_changes" value="0">
                        <input type="checkbox" name="notify_quote_status_changes" value="1" @checked(old('notify_quote_status_changes', $settings['notify_quote_status_changes']))>
                        <span>{{ __('Notify quote status changes') }}</span>
                    </label>
                    <label class="crm-checkbox-row">
                        <input type="hidden" name="notify_import_status_updates" value="0">
                        <input type="checkbox" name="notify_import_status_updates" value="1" @checked(old('notify_import_status_updates', $settings['notify_import_status_updates']))>
                        <span>{{ __('Notify import queued and completion updates') }}</span>
                    </label>
                </div>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>{{ __('AI') }}</x-slot:header>

                <div class="crm-form-grid">
                    <label class="crm-checkbox-row">
                        <input type="hidden" name="ai_enabled" value="0">
                        <input type="checkbox" name="ai_enabled" value="1" @checked(old('ai_enabled', $settings['ai_enabled']))>
                        <span>{{ __('Enable CRM AI actions') }}</span>
                    </label>
                    <x-admin-panel::select name="ai_driver" label="AI Driver" :options="$aiDrivers" :selected="old('ai_driver', $settings['ai_driver'])" required />
                    <x-admin-panel::input name="ai_model" label="AI Model Override" :value="old('ai_model', $settings['ai_model'])" placeholder="Use provider default when empty" />
                </div>
            </x-admin-panel::card>

            <div class="crm-form-actions">
                <x-admin-panel::button type="submit" icon="save">{{ __('Save Settings') }}</x-admin-panel::button>
                <x-admin-panel::button :href="route('crm.dashboard')" variant="ghost">{{ __('Cancel') }}</x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
