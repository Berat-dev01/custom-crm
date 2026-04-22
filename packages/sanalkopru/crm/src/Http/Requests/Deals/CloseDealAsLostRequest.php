<?php

namespace Sanalkopru\Crm\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CloseDealAsLostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('crm.deals.close');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lost_reason' => ['required', 'string', 'max:255'],
        ];
    }
}
