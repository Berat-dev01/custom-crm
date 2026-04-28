<?php

namespace Sanalkopru\Crm\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Models\DealStage;

class MoveDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('crm.deals.move');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stage_id' => ['required', 'integer', 'exists:deal_stages,id'],
            'position' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $stage = DealStage::query()->find($this->input('stage_id'));

            if ($stage?->is_lost && blank($this->input('lost_reason'))) {
                $validator->errors()->add('lost_reason', trans('crm::validation.deals.lost_reason_required_for_lost_stage'));
            }
        });
    }
}
