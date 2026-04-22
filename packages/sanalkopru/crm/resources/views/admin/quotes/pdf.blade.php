<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quote {{ $quote->quote_number }}</title>
    <style>
        body {
            color: #111827;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
        }

        .page {
            padding: 34px;
        }

        .header,
        .meta,
        .totals-row {
            display: table;
            width: 100%;
        }

        .left,
        .right,
        .totals-label,
        .totals-value {
            display: table-cell;
            vertical-align: top;
        }

        .right,
        .totals-value {
            text-align: right;
        }

        h1 {
            font-size: 28px;
            margin: 0 0 4px;
        }

        h2 {
            font-size: 14px;
            margin: 24px 0 8px;
        }

        .muted {
            color: #6b7280;
        }

        .box {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin-top: 22px;
            padding: 14px;
        }

        table {
            border-collapse: collapse;
            margin-top: 18px;
            width: 100%;
        }

        th {
            background: #f3f4f6;
            color: #374151;
            font-size: 11px;
            text-align: left;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px;
            vertical-align: top;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .totals {
            margin-left: auto;
            margin-top: 18px;
            width: 280px;
        }

        .totals-row {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 0;
        }

        .grand {
            font-size: 16px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="header">
            <div class="left">
                <h1>Quote</h1>
                <div class="muted">{{ $quote->quote_number }}</div>
            </div>
            <div class="right">
                <strong>{{ config('app.name', 'CRM') }}</strong><br>
                <span class="muted">Status: {{ ucfirst($quote->status) }}</span><br>
                <span class="muted">Valid Until: {{ $quote->valid_until?->format('Y-m-d') ?: '-' }}</span>
            </div>
        </section>

        <section class="box meta">
            <div class="left">
                <strong>Customer</strong><br>
                {{ $quote->company?->name ?: $quote->contact?->full_name ?: '-' }}<br>
                <span class="muted">{{ $quote->contact?->email ?: '' }}</span>
            </div>
            <div class="right">
                <strong>Sales</strong><br>
                {{ $quote->owner?->name ?: '-' }}<br>
                <span class="muted">{{ $quote->deal?->title ?: '' }}</span>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>Item</th>
                    <th class="number">Qty</th>
                    <th class="number">Unit</th>
                    <th class="number">Tax</th>
                    <th class="number">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->items as $item)
                    <tr>
                        <td>{{ $item->position }}</td>
                        <td>
                            <strong>{{ $item->name }}</strong><br>
                            <span class="muted">{{ $item->description }}</span>
                        </td>
                        <td class="number">{{ number_format((float) $item->quantity, 3) }}</td>
                        <td class="number">{{ $quote->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="number">{{ number_format((float) $item->tax_rate, 2) }}%</td>
                        <td class="number">{{ $quote->currency }} {{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <section class="totals">
            <div class="totals-row">
                <div class="totals-label">Subtotal</div>
                <div class="totals-value">{{ $quote->currency }} {{ number_format((float) $quote->subtotal, 2) }}</div>
            </div>
            <div class="totals-row">
                <div class="totals-label">Discount</div>
                <div class="totals-value">{{ $quote->currency }} {{ number_format((float) $quote->discount_total, 2) }}</div>
            </div>
            <div class="totals-row">
                <div class="totals-label">Tax</div>
                <div class="totals-value">{{ $quote->currency }} {{ number_format((float) $quote->tax_total, 2) }}</div>
            </div>
            <div class="totals-row grand">
                <div class="totals-label">Grand Total</div>
                <div class="totals-value">{{ $quote->currency }} {{ number_format((float) $quote->grand_total, 2) }}</div>
            </div>
        </section>

        @if($quote->notes)
            <h2>Notes</h2>
            <p>{{ $quote->notes }}</p>
        @endif

        @if($quote->terms)
            <h2>Terms</h2>
            <p>{{ $quote->terms }}</p>
        @endif
    </main>
</body>
</html>
