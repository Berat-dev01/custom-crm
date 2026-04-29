<?php

namespace App\Crm\Actions\Contacts;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\Activity;
use App\Crm\Models\Contact;
use App\Crm\Services\Activities\ActivityLogger;

class AddContactNote
{
    public function __construct(private readonly ActivityLogger $activities) {}

    public function handle(Contact $contact, string $body, ?Authenticatable $user = null): Activity
    {
        $activity = $this->activities->manual($contact, [
            'subject' => 'Contact note',
            'body' => $body,
            'type' => 'note',
        ], $user);

        $contact->forceFill(['last_contacted_at' => now()])->save();

        return $activity;
    }
}
