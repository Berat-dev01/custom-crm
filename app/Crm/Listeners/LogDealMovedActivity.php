<?php

namespace App\Crm\Listeners;

use App\Crm\Events\DealMoved;
use App\Crm\Services\Activities\ActivityLogger;

class LogDealMovedActivity
{
    public function __construct(private readonly ActivityLogger $activities) {}

    public function handle(DealMoved $event): void
    {
        $this->activities->system(
            $event->deal,
            'Deal moved',
            'deal_moved',
            $event->user,
            trim(($event->fromStage?->name ?: 'No stage').' -> '.$event->toStage->name),
            [
                'from_stage_id' => $event->fromStage?->id,
                'to_stage_id' => $event->toStage->id,
            ]
        );
    }
}
