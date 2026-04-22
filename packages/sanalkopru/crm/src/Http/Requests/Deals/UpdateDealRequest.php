<?php

namespace Sanalkopru\Crm\Http\Requests\Deals;

use Illuminate\Support\Facades\Gate;
use Sanalkopru\Crm\Http\Requests\Deals\Concerns\BuildsDealPayload;

class UpdateDealRequest extends StoreDealRequest
{
    use BuildsDealPayload;

    public function authorize(): bool
    {
        return Gate::allows('crm.deals.update');
    }
}
