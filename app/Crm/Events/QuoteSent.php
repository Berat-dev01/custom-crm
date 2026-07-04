<?php

namespace App\Crm\Events;

use App\Crm\Models\Quote;
use Illuminate\Contracts\Auth\Authenticatable;

class QuoteSent
{
    public function __construct(
        public readonly Quote $quote,
        public readonly ?Authenticatable $user = null
    ) {}
}
