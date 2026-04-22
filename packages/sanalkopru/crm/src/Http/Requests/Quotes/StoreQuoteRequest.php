<?php

namespace Sanalkopru\Crm\Http\Requests\Quotes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Sanalkopru\Crm\Http\Requests\Quotes\Concerns\BuildsQuotePayload;
use Sanalkopru\Crm\Models\Quote;

class StoreQuoteRequest extends FormRequest
{
    use BuildsQuotePayload;

    public function authorize(): bool
    {
        return Gate::allows('create', Quote::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->quoteRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function quoteRules(): array
    {
        return [
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'deal_id' => ['nullable', 'integer', 'exists:deals,id'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'sent', 'accepted', 'rejected', 'expired'])],
            'currency' => ['required', 'string', 'size:3'],
            'discount_type' => ['nullable', 'string', Rule::in(['fixed', 'percentage'])],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:10000'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.discount_type' => ['nullable', 'string', Rule::in(['fixed', 'percentage'])],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.position' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ];
    }
}
