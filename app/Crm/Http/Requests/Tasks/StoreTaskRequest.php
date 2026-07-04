<?php

namespace App\Crm\Http\Requests\Tasks;

use App\Crm\Http\Requests\Tasks\Concerns\BuildsTaskPayload;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTaskRequest extends FormRequest
{
    use BuildsTaskPayload;

    public function authorize(): bool
    {
        return Gate::allows('crm.tasks.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->taskRules();
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = $this->input('taskable_type');
            $id = $this->input('taskable_id');

            if (! $type && $id) {
                $validator->errors()->add('taskable_type', trans('crm::validation.tasks.related_record_type_required'));
            }

            if ($type && ! $id) {
                $validator->errors()->add('taskable_id', trans('crm::validation.tasks.related_record_required'));
            }

            if ($type && $id && ! $this->taskableExists($type, (int) $id)) {
                $validator->errors()->add('taskable_id', trans('crm::validation.tasks.related_record_invalid'));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function taskRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'taskable_type' => ['nullable', 'string', 'in:contact,company,deal,quote'],
            'taskable_id' => ['nullable', 'integer'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'reminder_at' => ['nullable', 'date'],
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
            'status' => ['required', 'string', 'in:open,in_progress,completed,cancelled'],
        ];
    }

    private function taskableExists(string $type, int $id): bool
    {
        $model = match ($type) {
            'contact' => Contact::class,
            'company' => Company::class,
            'deal' => Deal::class,
            'quote' => Quote::class,
            default => null,
        };

        return $model && $model::query()->whereKey($id)->exists();
    }
}
