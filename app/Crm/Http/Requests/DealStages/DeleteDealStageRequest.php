<?php

namespace App\Crm\Http\Requests\DealStages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DeleteDealStageRequest extends FormRequest
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
        $stage = $this->route('deal_stage');

        return [
            'replacement_stage_id' => [
                'nullable',
                'integer',
                Rule::exists('deal_stages', 'id')->whereNull('deleted_at'),
                Rule::notIn([$stage?->id]),
            ],
        ];
    }
}
