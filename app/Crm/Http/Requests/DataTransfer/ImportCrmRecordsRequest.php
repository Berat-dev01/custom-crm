<?php

namespace App\Crm\Http\Requests\DataTransfer;

use Illuminate\Foundation\Http\FormRequest;

class ImportCrmRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
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
