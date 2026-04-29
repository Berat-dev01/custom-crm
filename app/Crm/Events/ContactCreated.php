<?php

namespace App\Crm\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\Contact;

class ContactCreated
{
    public function __construct(
        public readonly Contact $contact,
        public readonly ?Authenticatable $user = null
    ) {}
}
