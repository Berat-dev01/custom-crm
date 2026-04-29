<?php

namespace App\Crm\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\Quote;

class QuoteSent
{
    public function __construct(
        public readonly Quote $quote,
        public readonly ?Authenticatable $user = null
    ) {}
}
