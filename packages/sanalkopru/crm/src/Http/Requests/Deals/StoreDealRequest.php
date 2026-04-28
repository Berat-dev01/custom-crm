<?php

namespace Sanalkopru\Crm\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Http\Requests\Deals\Concerns\BuildsDealPayload;
use Sanalkopru\Crm\Models\DealStage;

class StoreDealRequest extends FormRequest
{
    use BuildsDealPayload;

    public function authorize(): bool
    {
        return Gate::allows('crm.deals.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->dealRules();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $stage = DealStage::query()->find($this->input('stage_id'));

            if ($stage?->is_lost && blank($this->input('lost_reason'))) {
                $validator->errors()->add('lost_reason', trans('crm::validation.deals.lost_reason_required_for_lost_deal'));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function dealRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'stage_id' => ['required', 'integer', 'exists:deal_stages,id'],
            'value' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'probability' => ['required', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:open,won,lost'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'custom_fields_json' => ['nullable', 'json'],
        ];
    }
}
