<?php

namespace App\Crm\Http\Requests\DealStages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Crm\Http\Requests\DealStages\Concerns\BuildsDealStagePayload;

class StoreDealStageRequest extends FormRequest
{
    use BuildsDealStagePayload;

    public function authorize(): bool
    {
        return Gate::allows('crm.settings.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('deal_stages', 'slug')->whereNull('deleted_at')],
            'color' => ['required', 'string', 'max:32'],
            'position' => ['required', 'integer', 'min:1', 'max:1000'],
            'probability' => ['required', 'integer', 'min:0', 'max:100'],
            'is_won' => ['nullable', 'boolean', 'prohibited_if:is_lost,1'],
            'is_lost' => ['nullable', 'boolean', 'prohibited_if:is_won,1'],
        ];
    }
}
