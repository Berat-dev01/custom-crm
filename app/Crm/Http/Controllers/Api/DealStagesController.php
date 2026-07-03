<?php

namespace App\Crm\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use App\Crm\Http\Resources\Api\DealStageResource;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;

class DealStagesController extends Controller
{
    public function index(): mixed
    {
        Gate::authorize('viewAny', Deal::class);

        return DealStageResource::collection(DealStage::query()->ordered()->get());
    }
}
