<?php

namespace Sanalkopru\Crm\Http\Requests\DealStages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReorderDealStagesRequest extends FormRequest
{
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
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.id' => ['required', 'integer', 'exists:deal_stages,id'],
            'stages.*.position' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
