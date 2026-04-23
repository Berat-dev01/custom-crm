@extends('admin-panel::layouts.app')

@section('title', $deal->exists ? 'Edit Deal' : 'New Deal')
@section('page-title', $deal->exists ? 'Edit Deal' : 'New Deal')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="deals">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Deals</p>
                <h1>{{ $deal->exists ? 'Edit Deal' : 'New Deal' }}</h1>
            </div>
            <x-admin-panel::button :href="route('crm.deals.index')" variant="ghost" icon="arrow-left">
                Back
            </x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <form
                method="POST"
                action="{{ $deal->exists ? route('crm.deals.update', $deal) : route('crm.deals.store') }}"
                class="crm-form-grid"
            >
                @csrf
                @if($deal->exists)
                    @method('PUT')
                @endif

                <x-admin-panel::input name="title" label="Title" :value="$deal->title" required />
                <x-admin-panel::select name="stage_id" label="Stage" :options="$stages" :selected="$deal->stage_id" required />
                <x-admin-panel::select name="company_id" label="Company" :options="$companies" :selected="$deal->company_id" placeholder="No company" />
                <x-admin-panel::select name="contact_id" label="Contact" :options="$contacts" :selected="$deal->contact_id" value-field="id" label-field="full_name" placeholder="No contact" />
                <x-admin-panel::input name="value" label="Value" type="number" min="0" step="0.01" :value="$deal->value ?: 0" required />
                <x-admin-panel::select name="currency" label="Currency" :options="$currencies" :selected="$deal->currency ?: $defaultCurrency" required />
                <x-admin-panel::input name="probability" label="Probability" type="number" min="0" max="100" :value="$deal->probability ?? 0" required />
                <x-admin-panel::input name="expected_close_date" label="Expected Close Date" type="date" :value="$deal->expected_close_date?->format('Y-m-d')" />
                <x-admin-panel::select name="status" label="Status" :options="$statuses" :selected="$deal->status ?: 'open'" required />
                <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$deal->owner_id" placeholder="No owner" />
                <x-admin-panel::input name="lost_reason" label="Lost Reason" :value="$deal->lost_reason" />

                <x-admin-panel::select
                    name="tag_ids[]"
                    label="Tags"
                    :options="$tags"
                    :selected="old('tag_ids', $selectedTags)"
                    placeholder="No tags"
                    group-class="crm-span-2"
                    multiple
                />

                <x-admin-panel::textarea
                    name="custom_fields_json"
                    label="Custom Fields JSON"
                    class="crm-span-2"
                    :value="old('custom_fields_json', $deal->custom_fields ? json_encode($deal->custom_fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '')"
                    rows="6"
                    placeholder='{"source_campaign":"Spring"}'
                />

                <div class="crm-form-actions crm-span-2">
                    <x-admin-panel::button type="submit" icon="save">
                        {{ $deal->exists ? 'Save Deal' : 'Create Deal' }}
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.deals.index')" variant="ghost">
                        Cancel
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>
    </section>
@endsection
