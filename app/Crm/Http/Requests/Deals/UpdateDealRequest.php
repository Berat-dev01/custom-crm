<?php

namespace App\Crm\Http\Requests\Deals;

use App\Crm\Http\Requests\Deals\Concerns\BuildsDealPayload;
use Illuminate\Support\Facades\Gate;

class UpdateDealRequest extends StoreDealRequest
{
    use BuildsDealPayload;

    public function authorize(): bool
    {
        return Gate::allows('crm.deals.update');
    }
}
