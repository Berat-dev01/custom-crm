<?php

namespace Sanalkopru\Crm\Listeners;

use Sanalkopru\Crm\Events\QuoteSent;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Services\Activities\ActivityLogger;

class LogQuoteSentActivity
{
    public function __construct(private readonly ActivityLogger $activities) {}

    public function handle(QuoteSent $event): void
    {
        $activityable = $event->quote->deal ?: $event->quote;

        $this->activities->system(
            $activityable,
            'Quote sent',
            'quote_sent',
            $event->user,
            $event->quote->quote_number,
            [
                'quote_id' => $event->quote->id,
                'deal_id' => $activityable instanceof Deal ? $activityable->id : null,
            ]
        );
    }
}
