<?php

namespace App\Crm\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Crm\Models\Activity;

class StoreDealActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Activity::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:note,call,email,meeting'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
