<?php

namespace Sanalkopru\Crm\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('crm.activities.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:note,call,email,meeting'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
