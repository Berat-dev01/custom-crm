@extends('admin-panel::layouts.app')

@section('title', $quote->quote_number)
@section('page-title', $quote->quote_number)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/crm/css/crm.css') }}">
@endpush

@section('content')
    <section class="crm-admin-page" data-crm-module="quotes">
        @include('crm::admin.partials.status')

        @if(session('crm_ai_draft'))
            <div class="crm-highlight-box">
                <strong>AI Follow-up Draft</strong>
                <pre class="crm-muted" style="white-space: pre-wrap; margin: 0;">{{ session('crm_ai_draft') }}</pre>
            </div>
        @endif

        <header class="crm-admin-header crm-admin-header-row">
            <div>
                <p class="crm-admin-eyebrow">CRM / Quotes</p>
                <h1>{{ $quote->quote_number }}</h1>
            </div>

            <div class="crm-admin-actions">
                @can('export', $quote)
                    <x-admin-panel::button :href="route('crm.quotes.preview', $quote)" variant="outline" icon="eye">
                        PDF Preview
                    </x-admin-panel::button>
                    <x-admin-panel::button :href="route('crm.quotes.download', $quote)" variant="outline" icon="download">
                        Download PDF
                    </x-admin-panel::button>
                @endcan
                @can('update', $quote)
                    <x-admin-panel::button :href="route('crm.quotes.edit', $quote)" icon="pencil">
                        Edit
                    </x-admin-panel::button>
                @endcan
                @can('delete', $quote)
                    <form method="POST" action="{{ route('crm.quotes.destroy', $quote) }}" data-crm-confirm="Delete this quote?">
                        @csrf
                        @method('DELETE')
                        <x-admin-panel::button type="submit" variant="danger" icon="trash-2">
                            Delete
                        </x-admin-panel::button>
                    </form>
                @endcan
                <x-admin-panel::button :href="route('crm.quotes.index')" variant="ghost" icon="arrow-left">
                    Back
                </x-admin-panel::button>
            </div>
        </header>

        <div class="crm-admin-grid">
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Grand Total</span>
                <strong>{{ $quote->currency }} {{ number_format((float) $quote->grand_total, 2) }}</strong>
                <p>{{ ucfirst($quote->status) }}</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Subtotal</span>
                <strong>{{ $quote->currency }} {{ number_format((float) $quote->subtotal, 2) }}</strong>
                <p>Discount {{ $quote->currency }} {{ number_format((float) $quote->discount_total, 2) }}</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Tax</span>
                <strong>{{ $quote->currency }} {{ number_format((float) $quote->tax_total, 2) }}</strong>
                <p>{{ number_format((float) $quote->tax_rate, 2) }}% reference rate</p>
            </div>
            <div class="crm-admin-card">
                <span class="crm-admin-card-label">Valid Until</span>
                <strong>{{ $quote->valid_until?->format('Y-m-d') ?: '-' }}</strong>
                <p>{{ $quote->owner?->name ?: 'No owner' }}</p>
            </div>
        </div>

        <div class="crm-context-help">
            <strong>Status actions do not edit line items.</strong>
            <span>Use Duplicate for revisions, Send for customer delivery tracking, and Accept when the customer approves the offer.</span>
        </div>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Quote Summary
                </x-slot:header>

                <dl class="crm-detail-list">
                    <dt>Company</dt>
                    <dd>{{ $quote->company?->name ?: '-' }}</dd>
                    <dt>Contact</dt>
                    <dd>{{ $quote->contact?->full_name ?: '-' }}</dd>
                    <dt>Deal</dt>
                    <dd>{{ $quote->deal?->title ?: '-' }}</dd>
                    <dt>Sent At</dt>
                    <dd>{{ $quote->sent_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                    <dt>Accepted At</dt>
                    <dd>{{ $quote->accepted_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                    <dt>Rejected At</dt>
                    <dd>{{ $quote->rejected_at?->format('Y-m-d H:i') ?: '-' }}</dd>
                    <dt>Tags</dt>
                    <dd>{{ $quote->tags->pluck('name')->implode(', ') ?: '-' }}</dd>
                </dl>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    Status Actions
                </x-slot:header>

                <div class="crm-stack">
                    <div class="crm-row-actions">
                        @can('send', $quote)
                            <form method="POST" action="{{ route('crm.quotes.send', $quote) }}">
                                @csrf
                                @method('PATCH')
                                <x-admin-panel::button type="submit" variant="outline" icon="send">Send</x-admin-panel::button>
                            </form>
                        @endcan
                        @can('reject', $quote)
                            <form method="POST" action="{{ route('crm.quotes.reject', $quote) }}">
                                @csrf
                                @method('PATCH')
                                <x-admin-panel::button type="submit" variant="danger" icon="x">Reject</x-admin-panel::button>
                            </form>
                        @endcan
                        @can('update', $quote)
                            <form method="POST" action="{{ route('crm.quotes.expire', $quote) }}">
                                @csrf
                                @method('PATCH')
                                <x-admin-panel::button type="submit" variant="ghost" icon="clock">Expire</x-admin-panel::button>
                            </form>
                        @endcan
                        @can('create', \Sanalkopru\Crm\Models\Quote::class)
                            <form method="POST" action="{{ route('crm.quotes.duplicate', $quote) }}">
                                @csrf
                                <x-admin-panel::button type="submit" variant="ghost" icon="copy">Duplicate</x-admin-panel::button>
                            </form>
                        @endcan
                        @can('crm.ai.use')
                            <form method="POST" action="{{ route('crm.ai.follow-up') }}">
                                @csrf
                                <input type="hidden" name="quote_id" value="{{ $quote->id }}">
                                <input type="hidden" name="brief" value="Draft a polite follow-up for this quote.">
                                <x-admin-panel::button type="submit" variant="outline" icon="sparkles" :disabled="!$aiAvailable" title="{{ $aiAvailable ? 'Draft with AI' : 'AI is disabled or missing provider credentials' }}">
                                    AI Follow-up
                                </x-admin-panel::button>
                            </form>
                        @endcan
                    </div>

                    @can('accept', $quote)
                        <form method="POST" action="{{ route('crm.quotes.accept', $quote) }}" class="crm-action-panel">
                            @csrf
                            @method('PATCH')
                            @if($quote->deal)
                                <label class="crm-checkbox-row">
                                    <input type="checkbox" name="mark_deal_won" value="1">
                                    Mark related deal as won
                                </label>
                            @endif
                            <x-admin-panel::button type="submit" variant="success" icon="check">Accept Quote</x-admin-panel::button>
                        </form>
                    @endcan
                </div>
            </x-admin-panel::card>
        </div>

        <x-admin-panel::card>
            <x-slot:header>
                Line Items
            </x-slot:header>

            <x-admin-panel::table :headers="[
                ['label' => '#', 'width' => '50px'],
                ['label' => 'Item'],
                ['label' => 'Qty'],
                ['label' => 'Unit'],
                ['label' => 'Discount'],
                ['label' => 'Tax'],
                ['label' => 'Line Total'],
            ]">
                @foreach($quote->items as $item)
                    <tr>
                        <td>{{ $item->position }}</td>
                        <td>
                            <strong>{{ $item->name }}</strong>
                            @if($item->description)
                                <div class="crm-muted">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td>{{ number_format((float) $item->quantity, 3) }}</td>
                        <td>{{ $quote->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td>{{ $item->discount_type ? ucfirst($item->discount_type).' '.$item->discount_value : '-' }}</td>
                        <td>{{ number_format((float) $item->tax_rate, 2) }}%</td>
                        <td>{{ $quote->currency }} {{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </x-admin-panel::table>

            <div class="crm-totals">
                <div><span>Subtotal</span><strong>{{ $quote->currency }} {{ number_format((float) $quote->subtotal, 2) }}</strong></div>
                <div><span>Discount</span><strong>{{ $quote->currency }} {{ number_format((float) $quote->discount_total, 2) }}</strong></div>
                <div><span>Tax</span><strong>{{ $quote->currency }} {{ number_format((float) $quote->tax_total, 2) }}</strong></div>
                <div><span>Grand Total</span><strong>{{ $quote->currency }} {{ number_format((float) $quote->grand_total, 2) }}</strong></div>
            </div>
        </x-admin-panel::card>

        <div class="crm-two-column">
            <x-admin-panel::card>
                <x-slot:header>
                    Notes
                </x-slot:header>
                <p>{{ $quote->notes ?: '-' }}</p>
            </x-admin-panel::card>

            <x-admin-panel::card>
                <x-slot:header>
                    Terms
                </x-slot:header>
                <p>{{ $quote->terms ?: '-' }}</p>
            </x-admin-panel::card>
        </div>
    </section>
@endsection
