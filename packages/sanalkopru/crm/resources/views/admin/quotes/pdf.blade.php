<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Teklif {{ $quote->quote_number }}</title>
    <style>
        @page {
            margin: 24mm 18mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #111827;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.45;
            margin: 0;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        .page {
            position: relative;
        }

        .header,
        .party-grid,
        .summary-grid,
        .total-line,
        .footer {
            display: table;
            table-layout: fixed;
            width: 100%;
        }

        .header-left,
        .header-right,
        .party,
        .summary-cell,
        .total-label,
        .total-value,
        .footer-cell {
            display: table-cell;
            vertical-align: top;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
        }

        .header-right,
        .total-value,
        .footer-cell:last-child {
            text-align: right;
        }

        .logo {
            max-height: 44px;
            max-width: 150px;
        }

        .brand {
            font-size: 18px;
            font-weight: 700;
        }

        .muted {
            color: #6b7280;
        }

        .quote-title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0;
        }

        .quote-number {
            color: #374151;
            font-size: 13px;
            font-weight: 700;
            margin-top: 4px;
        }

        .badge {
            border: 1px solid #9ca3af;
            border-radius: 4px;
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            margin-top: 8px;
            padding: 3px 8px;
            text-transform: uppercase;
        }

        .section {
            margin-top: 18px;
        }

        .box {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 12px;
        }

        .party + .party,
        .summary-cell + .summary-cell {
            padding-left: 12px;
        }

        .label {
            color: #6b7280;
            display: block;
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .value {
            font-weight: 700;
        }

        table {
            border-collapse: collapse;
            margin-top: 10px;
            width: 100%;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th {
            background: #f3f4f6;
            color: #374151;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
        }

        th,
        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px 7px;
            vertical-align: top;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .description {
            color: #4b5563;
            margin-top: 4px;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .totals {
            margin-left: auto;
            margin-top: 14px;
            width: 290px;
        }

        .total-line {
            border-bottom: 1px solid #e5e7eb;
            padding: 5px 0;
        }

        .grand {
            border-bottom: 0;
            font-size: 15px;
            font-weight: 700;
            padding-top: 9px;
        }

        .notes-grid {
            display: table;
            table-layout: fixed;
            width: 100%;
        }

        .note-box {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .note-box + .note-box {
            padding-left: 12px;
        }

        .text-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            min-height: 72px;
            overflow-wrap: break-word;
            padding: 10px;
            word-break: break-word;
        }

        .footer {
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 9px;
            margin-top: 24px;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    @php
        $customerName = $quote->company?->name ?: $quote->contact?->full_name ?: '-';
        $customerLines = array_filter([
            $quote->contact?->full_name && $quote->company ? 'İlgili kişi: '.$quote->contact->full_name : null,
            $quote->contact?->email,
            $quote->contact?->phone,
            $quote->company?->address_line_1,
            collect([$quote->company?->city, $quote->company?->country])->filter()->implode(', '),
        ]);
        $companyLines = array_filter([
            $company['address'] ?? null,
            $company['phone'] ?? null,
            $company['email'] ?? null,
            $company['website'] ?? null,
            ($company['tax_office'] ?? null) ? 'Vergi Dairesi: '.$company['tax_office'] : null,
            ($company['tax_number'] ?? null) ? 'Vergi No: '.$company['tax_number'] : null,
        ]);
    @endphp

    <main class="page">
        <section class="header">
            <div class="header-left">
                @if($logoPath)
                    <img class="logo" src="{{ $logoPath }}" alt="{{ $company['name'] }}">
                @else
                    <div class="brand">{{ $company['name'] }}</div>
                @endif
                @foreach($companyLines as $line)
                    <div class="muted">{{ $line }}</div>
                @endforeach
            </div>
            <div class="header-right">
                <div class="quote-title">Teklif</div>
                <div class="quote-number">{{ $quote->quote_number }}</div>
                <div class="badge">{{ ucfirst($quote->status) }}</div>
            </div>
        </section>

        <section class="section party-grid">
            <div class="party box">
                <span class="label">Müşteri</span>
                <div class="value">{{ $customerName }}</div>
                @foreach($customerLines as $line)
                    <div class="muted">{{ $line }}</div>
                @endforeach
            </div>
            <div class="party box">
                <span class="label">Teklif Bilgileri</span>
                <div>Hazırlayan: <strong>{{ $quote->owner?->name ?: '-' }}</strong></div>
                <div>Geçerlilik: <strong>{{ $quote->valid_until?->format('Y-m-d') ?: '-' }}</strong></div>
                <div>Para Birimi: <strong>{{ $quote->currency }}</strong></div>
                <div>Fırsat: <strong>{{ $quote->deal?->title ?: '-' }}</strong></div>
            </div>
        </section>

        <section class="section summary-grid">
            <div class="summary-cell box">
                <span class="label">Ara Toplam</span>
                <div class="value">{{ $quote->currency }} {{ number_format((float) $quote->subtotal, 2) }}</div>
            </div>
            <div class="summary-cell box">
                <span class="label">İskonto</span>
                <div class="value">{{ $quote->currency }} {{ number_format((float) $quote->discount_total, 2) }}</div>
            </div>
            <div class="summary-cell box">
                <span class="label">KDV</span>
                <div class="value">{{ $quote->currency }} {{ number_format((float) $quote->tax_total, 2) }}</div>
            </div>
            <div class="summary-cell box">
                <span class="label">Genel Toplam</span>
                <div class="value">{{ $quote->currency }} {{ number_format((float) $quote->grand_total, 2) }}</div>
            </div>
        </section>

        <section class="section">
            <span class="label">Kalemler</span>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>Ürün / Hizmet</th>
                        <th class="number" style="width: 58px;">Miktar</th>
                        <th class="number" style="width: 82px;">Birim</th>
                        <th class="number" style="width: 76px;">İskonto</th>
                        <th class="number" style="width: 54px;">KDV</th>
                        <th class="number" style="width: 92px;">Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->items as $item)
                        <tr>
                            <td>{{ $item->position }}</td>
                            <td>
                                <strong>{{ $item->name }}</strong>
                                @if($item->description)
                                    <div class="description">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="number">{{ number_format((float) $item->quantity, 3) }}</td>
                            <td class="number">{{ $quote->currency }} {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="number">
                                @if($item->discount_type)
                                    {{ $item->discount_type === 'percentage' ? '%' : $quote->currency }} {{ number_format((float) $item->discount_value, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="number">{{ number_format((float) $item->tax_rate, 2) }}%</td>
                            <td class="number">{{ $quote->currency }} {{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <section class="totals">
                <div class="total-line">
                    <div class="total-label">Ara Toplam</div>
                    <div class="total-value">{{ $quote->currency }} {{ number_format((float) $quote->subtotal, 2) }}</div>
                </div>
                <div class="total-line">
                    <div class="total-label">İskonto</div>
                    <div class="total-value">{{ $quote->currency }} {{ number_format((float) $quote->discount_total, 2) }}</div>
                </div>
                <div class="total-line">
                    <div class="total-label">KDV</div>
                    <div class="total-value">{{ $quote->currency }} {{ number_format((float) $quote->tax_total, 2) }}</div>
                </div>
                <div class="total-line grand">
                    <div class="total-label">Genel Toplam</div>
                    <div class="total-value">{{ $quote->currency }} {{ number_format((float) $quote->grand_total, 2) }}</div>
                </div>
            </section>
        </section>

        <section class="section notes-grid">
            <div class="note-box">
                <span class="label">Notlar</span>
                <div class="text-box">{{ $quote->notes ?: '-' }}</div>
            </div>
            <div class="note-box">
                <span class="label">Şartlar</span>
                <div class="text-box">{{ $quote->terms ?: '-' }}</div>
            </div>
        </section>

        <section class="footer">
            <div class="footer-cell">Bu teklif {{ $company['name'] }} tarafından hazırlanmıştır.</div>
            <div class="footer-cell">{{ $quote->quote_number }}</div>
        </section>
    </main>
</body>
</html>
