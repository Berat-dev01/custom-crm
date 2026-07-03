<x-mail::message>
{{ trans('crm::notifications.quote_customer.greeting', ['name' => $quote->contact?->full_name ?? $quote->company?->name ?? '']) }}

{{ trans('crm::notifications.quote_customer.intro', ['company' => $companyProfile['name']]) }}

<x-mail::panel>
**{{ $quote->quote_number }}**

{{ trans('crm::notifications.quote_customer.total', ['value' => number_format((float) $quote->grand_total, 2).' '.$quote->currency]) }}
@if($quote->valid_until)
{{ trans('crm::notifications.quote_customer.valid_until', ['date' => $quote->valid_until->format('d.m.Y')]) }}
@endif
</x-mail::panel>

{{ trans('crm::notifications.quote_customer.attachment_note') }}

{{ trans('crm::notifications.quote_customer.outro') }}

{{ $companyProfile['name'] }}
</x-mail::message>
