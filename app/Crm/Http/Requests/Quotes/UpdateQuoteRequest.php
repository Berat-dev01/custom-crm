<?php

namespace App\Crm\Http\Requests\Quotes;

use App\Crm\Http\Requests\Quotes\Concerns\BuildsQuotePayload;
use Illuminate\Support\Facades\Gate;

class UpdateQuoteRequest extends StoreQuoteRequest
{
    use BuildsQuotePayload;

    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('quote'));
    }
}
