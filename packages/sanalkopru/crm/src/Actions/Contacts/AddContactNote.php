<?php

namespace Sanalkopru\Crm\Actions\Contacts;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Contact;

class AddContactNote
{
    public function handle(Contact $contact, string $body, ?Authenticatable $user = null): Activity
    {
        $activity = $contact->activities()->create([
            'subject' => 'Contact note',
            'body' => $body,
            'type' => 'note',
            'user_id' => $user?->getAuthIdentifier(),
            'created_by' => $user?->getAuthIdentifier(),
            'occurred_at' => now(),
        ]);

        $contact->forceFill(['last_contacted_at' => now()])->save();

        return $activity;
    }
}
