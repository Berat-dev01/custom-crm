@extends('admin-panel::layouts.app')

@section('title', $contact->exists ? 'Edit Contact' : 'New Contact')
@section('page-title', $contact->exists ? 'Edit Contact' : 'New Contact')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="contacts">
        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Contacts</p>
                <h1>{{ $contact->exists ? 'Edit Contact' : 'New Contact' }}</h1>
            </div>
            <x-admin-panel::button :href="route('crm.contacts.index')" variant="ghost" icon="arrow-left">
                Back
            </x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <form
                method="POST"
                action="{{ $contact->exists ? route('crm.contacts.update', $contact) : route('crm.contacts.store') }}"
                class="crm-form-grid"
            >
                @csrf
                @if($contact->exists)
                    @method('PUT')
                @endif

                <x-admin-panel::input name="first_name" label="First Name" :value="$contact->first_name" />
                <x-admin-panel::input name="last_name" label="Last Name" :value="$contact->last_name" />
                <x-admin-panel::input name="full_name" label="Display Name" :value="$contact->full_name" required />
                <x-admin-panel::input name="email" label="Email" type="email" :value="$contact->email" />
                <x-admin-panel::input name="phone" label="Phone" :value="$contact->phone" maxlength="50" />
                <x-admin-panel::input name="title" label="Title" :value="$contact->title" />
                <x-admin-panel::select name="company_id" label="Company" :options="$companies" :selected="$contact->company_id" placeholder="No company" />
                <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$contact->owner_id" placeholder="No owner" />
                <x-admin-panel::select name="lifecycle_stage" label="Lifecycle Stage" :options="$lifecycleStages" :selected="$contact->lifecycle_stage ?: 'lead'" required />
                <x-admin-panel::select name="source" label="Source" :options="$sources" :selected="$contact->source" placeholder="No source" />
                <x-admin-panel::input name="last_contacted_at" label="Last Contacted At" type="datetime-local" :value="$contact->last_contacted_at?->format('Y-m-d\\TH:i')" />

                <div class="form-group crm-span-2">
                    <label class="form-label" for="tag_ids">Tags</label>
                    <select id="tag_ids" name="tag_ids[]" class="form-control" multiple>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" @selected(in_array($tag->id, old('tag_ids', $selectedTags), true))>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('tag_ids')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                </div>

                <x-admin-panel::textarea
                    name="custom_fields_json"
                    label="Custom Fields JSON"
                    class="crm-span-2"
                    :value="old('custom_fields_json', $contact->custom_fields ? json_encode($contact->custom_fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '')"
                    rows="6"
                    placeholder='{"preferred_channel":"email"}'
                />

                <div class="crm-form-actions crm-span-2">
                    <x-admin-panel::button type="submit" icon="save">
                        {{ $contact->exists ? 'Save Contact' : 'Create Contact' }}
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.contacts.index')" variant="ghost">
                        Cancel
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>
    </section>
@endsection
