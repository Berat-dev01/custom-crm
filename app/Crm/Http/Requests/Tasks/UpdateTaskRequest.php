<?php

namespace App\Crm\Http\Requests\Tasks;

use App\Crm\Http\Requests\Tasks\Concerns\BuildsTaskPayload;
use Illuminate\Support\Facades\Gate;

class UpdateTaskRequest extends StoreTaskRequest
{
    use BuildsTaskPayload;

    public function authorize(): bool
    {
        return Gate::allows('crm.tasks.update');
    }
}
