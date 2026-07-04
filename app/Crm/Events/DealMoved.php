<?php

namespace App\Crm\Events;

use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use Illuminate\Contracts\Auth\Authenticatable;

class DealMoved
{
    public function __construct(
        public readonly Deal $deal,
        public readonly ?DealStage $fromStage,
        public readonly DealStage $toStage,
        public readonly ?Authenticatable $user = null
    ) {}
}
