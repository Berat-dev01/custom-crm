<?php

namespace App\Crm\Http\Requests\DataTransfer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ImportCrmRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $module = $this->route()->defaults['module'] ?? null;

        if (! $module) {
            return false;
        }

        return Gate::allows("crm.{$module}.import");
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ];
    }
}
