<?php

namespace App\Crm\Events;

use App\Crm\Models\Contact;
use Illuminate\Contracts\Auth\Authenticatable;

class ContactCreated
{
    public function __construct(
        public readonly Contact $contact,
        public readonly ?Authenticatable $user = null
    ) {}
}
