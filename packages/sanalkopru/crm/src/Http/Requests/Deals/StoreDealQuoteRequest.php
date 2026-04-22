<?php

namespace Sanalkopru\Crm\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Models\Quote;

class StoreDealQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Quote::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item_name' => ['required', 'string', 'max:255'],
            'item_description' => ['nullable', 'string', 'max:1000'],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:999999'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:4000'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
