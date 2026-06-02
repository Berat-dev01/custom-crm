<?php

namespace App\Crm\Http\Requests\Contacts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class BulkDeleteContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('crm.contacts.delete');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_ids' => ['required', 'array', 'min:1', 'max:500'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
        ];
    }
}
