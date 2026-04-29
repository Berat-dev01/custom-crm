<?php

namespace App\Crm\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;

class DealMoved
{
    public function __construct(
        public readonly Deal $deal,
        public readonly ?DealStage $fromStage,
        public readonly DealStage $toStage,
        public readonly ?Authenticatable $user = null
    ) {}
}
