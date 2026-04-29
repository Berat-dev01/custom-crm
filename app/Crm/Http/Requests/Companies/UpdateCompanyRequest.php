<?php

namespace App\Crm\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Crm\Http\Requests\Companies\Concerns\BuildsCompanyPayload;
use App\Crm\Models\Company;

class UpdateCompanyRequest extends FormRequest
{
    use BuildsCompanyPayload;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('company'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Company $company */
        $company = $this->route('company');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')->ignore($company->id)->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:80', Rule::unique('companies', 'tax_number')->ignore($company->id)->whereNull('deleted_at')],
            'tax_office' => ['nullable', 'string', 'max:120'],
            'sector' => ['nullable', 'string', 'max:120'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'custom_fields_json' => ['nullable', 'json'],
        ];
    }
}
