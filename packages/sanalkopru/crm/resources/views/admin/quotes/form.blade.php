@extends('admin-panel::layouts.app')

@section('title', $quote->exists ? __('Edit Quote') : __('New Quote'))
@section('page-title', $quote->exists ? __('Edit Quote') : __('New Quote'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    @php
        $existingItems = $quote->exists
            ? $quote->items->map(fn($item) => [
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_type' => $item->discount_type,
                'discount_value' => $item->discount_value,
                'tax_rate' => $item->tax_rate,
                'position' => $item->position,
            ])->values()->all()
            : [[
                'name' => '',
                'description' => '',
                'quantity' => '1.000',
                'unit_price' => '0.00',
                'discount_type' => null,
                'discount_value' => '0.00',
                'tax_rate' => $defaultTaxRate,
                'position' => 1,
            ]];
        $itemRows = old('items', $existingItems);
    @endphp

    <section class="crm-admin-page" data-crm-module="quotes">
        @include('crm::admin.partials.status')

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">{{ __('CRM / Quotes') }}</p>
                <h1>{{ $quote->exists ? __('Edit Quote') : __('New Quote') }}</h1>
            </div>
            <x-admin-panel::button :href="$quote->exists ? route('crm.quotes.show', $quote) : route('crm.quotes.index')" variant="ghost" icon="arrow-left">
                {{ __('Back') }}
            </x-admin-panel::button>
        </header>

        <div class="crm-context-help">
            <strong>{{ __('Quote totals are recalculated by the backend.') }}</strong>
            <span>{{ __('Use line discounts for one item, quote discount for the whole offer, and link a deal when this quote belongs to an opportunity.') }}</span>
        </div>

        <form
            method="POST"
            action="{{ $quote->exists ? route('crm.quotes.update', $quote) : route('crm.quotes.store') }}"
            class="crm-stack"
            data-crm-quote-form
        >
            @csrf
            @if($quote->exists)
                @method('PUT')
            @endif

            <x-admin-panel::card>
                <x-slot:header>
                    {{ __('Quote Details') }}
                </x-slot:header>

                <div class="crm-form-grid">
                    <x-admin-panel::select name="contact_id" label="Contact" :options="$contacts" :selected="old('contact_id', $quote->contact_id)" placeholder="No contact" />
                    <x-admin-panel::select name="company_id" label="Company" :options="$companies" :selected="old('company_id', $quote->company_id)" placeholder="No company" />
                    <x-admin-panel::select name="deal_id" label="Deal" :options="$deals" :selected="old('deal_id', $quote->deal_id)" placeholder="No deal" />
                    <x-admin-panel::select name="owner_id" label="Owner" :options="$owners" :selected="old('owner_id', $quote->owner_id)" placeholder="No owner" />
                    <x-admin-panel::select name="status" label="Status" :options="$statuses" :selected="old('status', $quote->status ?: 'draft')" required />
                    <x-admin-panel::select name="currency" label="Currency" :options="$currencies" :selected="old('currency', $quote->currency ?: $defaultCurrency)" required />
                    <x-admin-panel::select name="discount_type" label="Quote Discount" :options="$discountTypes" :selected="old('discount_type', $quote->discount_type)" placeholder="No discount" />
                    <x-admin-panel::input name="discount_value" label="Discount Value" type="number" min="0" step="0.01" :value="old('discount_value', $quote->discount_value ?: 0)" />
                    <x-admin-panel::input name="valid_until" label="Valid Until" type="date" :value="old('valid_until', $quote->valid_until?->format('Y-m-d'))" />

                    <x-admin-panel::select
                        name="tag_ids[]"
                        label="Tags"
                        :options="$tags"
                        :selected="old('tag_ids', $selectedTags)"
                        placeholder="No tags"
                        group-class="crm-span-2"
                        multiple
                    />

                    <x-admin-panel::textarea name="notes" label="Notes" class="crm-span-2" :value="old('notes', $quote->notes)" rows="3" />
                    <x-admin-panel::textarea name="terms" label="Terms" class="crm-span-2" :value="old('terms', $quote->terms ?: $defaultTerms)" rows="4" />
                </div>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    {{ __('Line Items') }}
                </x-slot:header>

                <x-slot:headerActions>
                    <x-admin-panel::button type="button" size="sm" variant="outline" icon="plus" data-crm-add-quote-item>
                        {{ __('Add Line') }}
                    </x-admin-panel::button>
                </x-slot:headerActions>

                <div class="crm-quote-items" data-crm-quote-items data-default-tax-rate="{{ $defaultTaxRate }}">
                    @foreach($itemRows as $index => $item)
                        <div class="crm-quote-item" data-crm-quote-item>
                            <div class="crm-quote-item-toolbar">
                                <strong>{{ __('Line') }} <span data-crm-quote-item-number>{{ $loop->iteration }}</span></strong>
                                <div class="crm-row-actions">
                                    <button type="button" class="crm-icon-button" title="{{ __('Move up') }}" data-crm-quote-item-up>&uarr;</button>
                                    <button type="button" class="crm-icon-button" title="{{ __('Move down') }}" data-crm-quote-item-down>&darr;</button>
                                    <button type="button" class="crm-icon-button" title="{{ __('Remove line') }}" data-crm-remove-quote-item>&times;</button>
                                </div>
                            </div>

                            <div class="crm-form-grid">
                                <input type="hidden" name="items[{{ $index }}][position]" value="{{ $item['position'] ?? ($index + 1) }}" data-crm-quote-item-position>

                                <div class="form-group">
                                    <label class="form-label">{{ __('Name') }}</label>
                                    <input name="items[{{ $index }}][name]" class="form-control" value="{{ $item['name'] ?? '' }}" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Quantity') }}</label>
                                    <input name="items[{{ $index }}][quantity]" class="form-control" type="number" min="0.001" step="0.001" value="{{ $item['quantity'] ?? '1.000' }}" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Unit Price') }}</label>
                                    <input name="items[{{ $index }}][unit_price]" class="form-control" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? '0.00' }}" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Discount Type') }}</label>
                                    <select name="items[{{ $index }}][discount_type]" class="form-control">
                                        <option value="">{{ __('No discount') }}</option>
                                        @foreach($discountTypes as $value => $label)
                                            <option value="{{ $value }}" @selected(($item['discount_type'] ?? null) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Discount Value') }}</label>
                                    <input name="items[{{ $index }}][discount_value]" class="form-control" type="number" min="0" step="0.01" value="{{ $item['discount_value'] ?? '0.00' }}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Tax Rate') }}</label>
                                    <input name="items[{{ $index }}][tax_rate]" class="form-control" type="number" min="0" max="100" step="0.01" value="{{ $item['tax_rate'] ?? $defaultTaxRate }}">
                                </div>
                                <div class="form-group crm-span-2">
                                    <label class="form-label">{{ __('Description') }}</label>
                                    <textarea name="items[{{ $index }}][description]" class="form-control" rows="2">{{ $item['description'] ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-admin-panel::card>

            <div class="crm-form-actions">
                <x-admin-panel::button type="submit" icon="save">
                    {{ $quote->exists ? __('Save Quote') : __('Create Quote') }}
                </x-admin-panel::button>
                <x-admin-panel::button :href="$quote->exists ? route('crm.quotes.show', $quote) : route('crm.quotes.index')" variant="ghost">
                    {{ __('Cancel') }}
                </x-admin-panel::button>
            </div>
        </form>
    </section>
@endsection
