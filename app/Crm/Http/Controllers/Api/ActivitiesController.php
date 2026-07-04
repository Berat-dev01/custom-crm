<?php

namespace App\Crm\Http\Controllers\Api;

use App\Crm\Http\Requests\Activities\StoreActivityRequest;
use App\Crm\Http\Resources\Api\ActivityResource;
use App\Crm\Models\Activity;
use App\Crm\Services\Activities\ActivityLogger;
use App\Crm\Services\Activities\ActivityQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ActivitiesController extends Controller
{
    public function __construct(private readonly ActivityQuery $activities) {}

    public function index(Request $request): mixed
    {
        Gate::authorize('viewAny', Activity::class);

        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'in:note,call,email,meeting'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('crm.api.max_per_page', 100)],
        ]);

        return ActivityResource::collection($this->activities->paginate($request));
    }

    public function store(StoreActivityRequest $request, ActivityLogger $logger): mixed
    {
        $data = $request->activityData();
        $activity = $logger->manual($data['activityable'], $data['payload'], $request->user());

        return (new ActivityResource($activity))
            ->additional(['message' => trans('crm::messages.activities.created')])
            ->response()
            ->setStatusCode(201);
    }
}
