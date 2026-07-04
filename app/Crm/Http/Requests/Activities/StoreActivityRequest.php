<?php

namespace App\Crm\Http\Requests\Activities;

use App\Crm\Http\Requests\Activities\Concerns\BuildsActivityPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreActivityRequest extends FormRequest
{
    use BuildsActivityPayload;

    public function authorize(): bool
    {
        return Gate::allows('crm.activities.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'activityable_type' => ['required', 'string', 'in:contact,company,deal,quote'],
            'activityable_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:note,call,email,meeting'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
