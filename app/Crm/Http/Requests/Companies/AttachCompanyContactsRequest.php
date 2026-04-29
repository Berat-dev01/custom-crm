<?php

namespace App\Crm\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AttachCompanyContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('company'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact_ids' => ['required', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
        ];
    }
}
