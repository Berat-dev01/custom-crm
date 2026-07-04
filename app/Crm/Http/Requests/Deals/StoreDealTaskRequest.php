<?php

namespace App\Crm\Http\Requests\Deals;

use App\Crm\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDealTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Task::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'reminder_at' => ['nullable', 'date'],
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
        ];
    }
}
