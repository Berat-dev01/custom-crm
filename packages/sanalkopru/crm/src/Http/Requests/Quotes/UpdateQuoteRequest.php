<?php

namespace Sanalkopru\Crm\Http\Requests\Quotes;

use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Http\Requests\Quotes\Concerns\BuildsQuotePayload;

class UpdateQuoteRequest extends StoreQuoteRequest
{
    use BuildsQuotePayload;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('quote'));
    }
}
