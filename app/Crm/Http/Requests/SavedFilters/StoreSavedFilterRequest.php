<?php

namespace App\Crm\Http\Requests\SavedFilters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreSavedFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $module = $this->string('module')->toString();

        return in_array($module, ['contacts', 'companies', 'deals', 'tasks', 'quotes', 'activities'], true)
            && Gate::allows("crm.{$module}.view");
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'module' => ['required', 'string', 'in:contacts,companies,deals,tasks,quotes,activities'],
            'name' => ['required', 'string', 'max:120'],
            'visibility' => ['required', 'string', 'in:private,public'],
            'filters' => ['array'],
        ];
    }
}
