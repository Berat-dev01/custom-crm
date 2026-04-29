<?php

namespace App\Crm\Http\Requests\Tags;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class BulkTagRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('crm.tags.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'taggable_type' => ['required', 'string', 'in:contact,company,deal,quote'],
            'record_ids' => ['required_without:contact_ids', 'array', 'min:1'],
            'record_ids.*' => ['integer'],
            'contact_ids' => ['required_without:record_ids', 'array', 'min:1'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
            'tag_ids' => ['required', 'array', 'min:1'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'mode' => ['required', 'string', 'in:attach,detach'],
        ];
    }
}
