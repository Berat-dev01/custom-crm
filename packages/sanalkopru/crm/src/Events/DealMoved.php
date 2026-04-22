<?php

namespace Sanalkopru\Crm\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;

class DealMoved
{
    public function __construct(
        public readonly Deal $deal,
        public readonly ?DealStage $fromStage,
        public readonly DealStage $toStage,
        public readonly ?Authenticatable $user = null
    ) {}
}
