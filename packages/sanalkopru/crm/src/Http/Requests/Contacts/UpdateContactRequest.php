<?php

namespace Sanalkopru\Crm\Http\Requests\Contacts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Sanalkopru\Crm\Http\Requests\Contacts\Concerns\BuildsContactPayload;
use Sanalkopru\Crm\Models\Contact;

class UpdateContactRequest extends FormRequest
{
    use BuildsContactPayload;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('contact'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Contact $contact */
        $contact = $this->route('contact');

        return [
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'full_name' => ['required_without:first_name', 'nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('contacts', 'email')->ignore($contact->id)->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:160'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'lifecycle_stage' => ['required', 'string', 'max:40'],
            'source' => ['nullable', 'string', 'max:80'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'last_contacted_at' => ['nullable', 'date'],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'custom_fields_json' => ['nullable', 'json'],
        ];
    }
}
