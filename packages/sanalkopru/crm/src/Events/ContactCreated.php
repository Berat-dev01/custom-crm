<?php

namespace Sanalkopru\Crm\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Contact;

class ContactCreated
{
    public function __construct(
        public readonly Contact $contact,
        public readonly ?Authenticatable $user = null
    ) {}
}
