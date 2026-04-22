<?php

namespace Sanalkopru\Crm\Http\Requests\Contacts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreContactNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('crm.activities.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
