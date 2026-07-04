<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ trans('crm::public.quote.title', ['quote' => $quote->quote_number]) }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; background: #f3f4f6; color: #111827; }
        .pq-wrap { max-width: 760px; margin: 0 auto; padding: 24px 16px 64px; }
        .pq-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08); padding: 28px; margin-bottom: 20px; }
        .pq-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
        .pq-logo { max-height: 56px; max-width: 200px; }
        .pq-muted { color: #6b7280; font-size: 14px; }
        .pq-status { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .pq-status.sent { background: #eff6ff; color: #1d4ed8; }
        .pq-status.accepted { background: #ecfdf5; color: #047857; }
        .pq-status.rejected { background: #fef2f2; color: #b91c1c; }
        .pq-status.expired, .pq-status.draft { background: #f9fafb; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: .03em; }
        td.num, th.num { text-align: right; }
        .pq-totals { margin-top: 16px; margin-left: auto; max-width: 320px; }
        .pq-totals div { display: flex; justify-content: space-between; padding: 6px 8px; font-size: 14px; }
        .pq-totals .grand { font-weight: 700; font-size: 16px; border-top: 2px solid #111827; margin-top: 4px; padding-top: 10px; }
        .pq-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; }
        .pq-btn { display: inline-block; padding: 12px 22px; border-radius: 8px; border: 0; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .pq-btn.accept { background: #047857; color: #fff; }
        .pq-btn.reject { background: #fff; color: #b91c1c; border: 1px solid #fca5a5; }
        .pq-btn.download { background: #f3f4f6; color: #111827; border: 1px solid #d1d5db; }
        textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px; font-family: inherit; font-size: 14px; margin-top: 8px; }
        .pq-flash { background: #ecfdf5; color: #047857; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
        .pq-block { margin-top: 20px; }
        .pq-block h3 { margin: 0 0 6px; font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: .03em; }
        .pq-block p { margin: 0; white-space: pre-line; font-size: 14px; }
    </style>
</head>
<body>
<div class="pq-wrap">
    @if(session('public_quote_status'))
        <div class="pq-flash">{{ session('public_quote_status') }}</div>
    @endif

    <div class="pq-card">
        <div class="pq-header">
            <div>
                @if($logoUrl)
                    <img class="pq-logo" src="{{ $logoUrl }}" alt="{{ $companyProfile['name'] }}">
                @else
                    <h2 style="margin:0">{{ $companyProfile['name'] }}</h2>
                @endif
                <p class="pq-muted">
                    {{ $companyProfile['address'] }}<br>
                    {{ $companyProfile['email'] }} {{ $companyProfile['phone'] ? ' · '.$companyProfile['phone'] : '' }}
                </p>
            </div>
            <div style="text-align:right">
                <h1 style="margin:0;font-size:22px">{{ trans('crm::public.quote.title', ['quote' => $quote->quote_number]) }}</h1>
                @if($quote->valid_until)
                    <p class="pq-muted">{{ trans('crm::public.quote.valid_until') }}: {{ $quote->valid_until->format('d.m.Y') }}</p>
                @endif
            </div>
        </div>

        <div class="pq-block">
            <h3>{{ trans('crm::public.quote.to') }}</h3>
            <p>
                {{ $quote->contact?->full_name }}
                @if($quote->company){{ $quote->contact ? ' — ' : '' }}{{ $quote->company->name }}@endif
            </p>
        </div>
    </div>

    <div class="pq-status {{ $quote->status }}">
        {{ trans('crm::public.quote.status_'.$quote->status) }}
    </div>

    <div class="pq-card">
        <table>
            <thead>
                <tr>
                    <th>{{ trans('crm::public.quote.item') }}</th>
                    <th class="num">{{ trans('crm::public.quote.quantity') }}</th>
                    <th class="num">{{ trans('crm::public.quote.unit_price') }}</th>
                    <th class="num">{{ trans('crm::public.quote.line_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->name }}</strong>
                            @if($item->description)<div class="pq-muted">{{ $item->description }}</div>@endif
                        </td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                        <td class="num">{{ number_format((float) $item->unit_price, 2) }} {{ $quote->currency }}</td>
                        <td class="num">{{ number_format((float) $item->line_total, 2) }} {{ $quote->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pq-totals">
            <div><span>{{ trans('crm::public.quote.subtotal') }}</span><span>{{ number_format((float) $quote->subtotal, 2) }} {{ $quote->currency }}</span></div>
            @if((float) $quote->discount_total > 0)
                <div><span>{{ trans('crm::public.quote.discount') }}</span><span>-{{ number_format((float) $quote->discount_total, 2) }} {{ $quote->currency }}</span></div>
            @endif
            <div><span>{{ trans('crm::public.quote.tax') }} ({{ rtrim(rtrim(number_format((float) $quote->tax_rate, 2), '0'), '.') }}%)</span><span>{{ number_format((float) $quote->tax_total, 2) }} {{ $quote->currency }}</span></div>
            <div class="grand"><span>{{ trans('crm::public.quote.grand_total') }}</span><span>{{ number_format((float) $quote->grand_total, 2) }} {{ $quote->currency }}</span></div>
        </div>

        @if($quote->notes)
            <div class="pq-block">
                <h3>{{ trans('crm::public.quote.notes') }}</h3>
                <p>{{ $quote->notes }}</p>
            </div>
        @endif

        @if($quote->terms)
            <div class="pq-block">
                <h3>{{ trans('crm::public.quote.terms') }}</h3>
                <p>{{ $quote->terms }}</p>
            </div>
        @endif
    </div>

    <div class="pq-card">
        <div class="pq-actions">
            @if($quote->status === \App\Crm\Models\Quote::STATUS_SENT)
                <form method="POST" action="{{ route('crm.public.quote.accept', $quote->public_token) }}" onsubmit="return confirm(@js(trans('crm::public.quote.confirm_accept')));">
                    @csrf
                    <button type="submit" class="pq-btn accept">{{ trans('crm::public.quote.accept') }}</button>
                </form>
            @endif
            <a class="pq-btn download" href="{{ route('crm.public.quote.download', $quote->public_token) }}">{{ trans('crm::public.quote.download_pdf') }}</a>
        </div>

        @if($quote->status === \App\Crm\Models\Quote::STATUS_SENT)
            <form method="POST" action="{{ route('crm.public.quote.reject', $quote->public_token) }}" class="pq-block" onsubmit="return confirm(@js(trans('crm::public.quote.confirm_reject')));">
                @csrf
                <h3>{{ trans('crm::public.quote.reject') }}</h3>
                <textarea name="reason" rows="3" maxlength="2000" placeholder="{{ trans('crm::public.quote.reject_reason') }}"></textarea>
                <div style="margin-top:10px">
                    <button type="submit" class="pq-btn reject">{{ trans('crm::public.quote.reject') }}</button>
                </div>
            </form>
        @endif
    </div>
</div>
</body>
</html>
