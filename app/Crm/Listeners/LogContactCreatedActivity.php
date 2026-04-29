<?php

namespace App\Crm\Listeners;

use App\Crm\Events\ContactCreated;
use App\Crm\Services\Activities\ActivityLogger;

class LogContactCreatedActivity
{
    public function __construct(private readonly ActivityLogger $activities) {}

    public function handle(ContactCreated $event): void
    {
        $this->activities->system(
            $event->contact,
            'Contact created',
            'system',
            $event->user,
            $event->contact->full_name
        );
    }
}
