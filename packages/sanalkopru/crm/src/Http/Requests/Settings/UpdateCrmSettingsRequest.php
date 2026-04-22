<?php

namespace Sanalkopru\Crm\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Sanalkopru\Crm\Support\Ai\AiDriver;

class UpdateCrmSettingsRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:160'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:80'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'tax_number' => ['nullable', 'string', 'max:80'],
            'tax_office' => ['nullable', 'string', 'max:120'],
            'default_currency' => ['required', 'string', 'size:3'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'quote_prefix' => ['required', 'string', 'max:20'],
            'quote_terms' => ['nullable', 'string', 'max:10000'],
            'notify_task_reminders' => ['nullable', 'boolean'],
            'notify_quote_status_changes' => ['nullable', 'boolean'],
            'ai_enabled' => ['nullable', 'boolean'],
            'ai_driver' => ['required', 'string', Rule::in(AiDriver::values())],
            'ai_model' => ['nullable', 'string', 'max:120'],
        ];
    }
}
