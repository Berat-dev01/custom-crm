<?php

namespace Sanalkopru\Crm\Http\Requests\Quotes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AcceptQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('accept', $this->route('quote'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mark_deal_won' => ['nullable', 'boolean'],
        ];
    }
}
