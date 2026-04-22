<?php

namespace Sanalkopru\Crm\Http\Requests\Tasks;

use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Http\Requests\Tasks\Concerns\BuildsTaskPayload;

class UpdateTaskRequest extends StoreTaskRequest
{
    use BuildsTaskPayload;

    public function authorize(): bool
    {
        return Gate::allows('crm.tasks.update');
    }
}
