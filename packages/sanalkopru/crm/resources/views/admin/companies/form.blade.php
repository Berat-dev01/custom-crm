@extends('admin-panel::layouts.app')

@section('title', $company->exists ? 'Edit Company' : 'New Company')
@section('page-title', $company->exists ? 'Edit Company' : 'New Company')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="companies">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Companies</p>
                <h1>{{ $company->exists ? 'Edit Company' : 'New Company' }}</h1>
            </div>
            <x-admin-panel::button :href="route('crm.companies.index')" variant="ghost" icon="arrow-left">
                Back
            </x-admin-panel::button>
        </header>

        <x-admin-panel::card>
            <form
                method="POST"
                action="{{ $company->exists ? route('crm.companies.update', $company) : route('crm.companies.store') }}"
                class="crm-form-grid"
            >
                @csrf
                @if($company->exists)
                    @method('PUT')
                @endif

                <x-admin-panel::input name="name" label="Name" :value="$company->name" required />
                <x-admin-panel::input name="email" label="Email" type="email" :value="$company->email" />
                <x-admin-panel::input name="phone" label="Phone" :value="$company->phone" maxlength="50" />
                <x-admin-panel::input name="website" label="Website" type="url" :value="$company->website" />
                <x-admin-panel::input name="tax_number" label="Tax Number" :value="$company->tax_number" />
                <x-admin-panel::input name="tax_office" label="Tax Office" :value="$company->tax_office" />
                <x-admin-panel::select name="sector" label="Sector" :options="$sectors" :selected="$company->sector" placeholder="No sector" />
                <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="$company->owner_id" placeholder="No owner" />
                <x-admin-panel::input name="address_line_1" label="Address Line 1" :value="$company->address_line_1" />
                <x-admin-panel::input name="address_line_2" label="Address Line 2" :value="$company->address_line_2" />
                <x-admin-panel::input name="city" label="City" :value="$company->city" />
                <x-admin-panel::input name="state" label="State" :value="$company->state" />
                <x-admin-panel::input name="postal_code" label="Postal Code" :value="$company->postal_code" />
                <x-admin-panel::input name="country" label="Country" :value="$company->country" />

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
                    :value="old('custom_fields_json', $company->custom_fields ? json_encode($company->custom_fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '')"
                    rows="6"
                    placeholder='{"erp_code":"ACME-001"}'
                />

                <div class="crm-form-actions crm-span-2">
                    <x-admin-panel::button type="submit" icon="save">
                        {{ $company->exists ? 'Save Company' : 'Create Company' }}
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.companies.index')" variant="ghost">
                        Cancel
                    </x-admin-panel::button>
                </div>
            </form>
        </x-admin-panel::card>
    </section>
@endsection
