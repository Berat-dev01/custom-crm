<?php

namespace App\Crm\Http\Controllers\Api;

use App\Crm\Http\Resources\Api\TagResource;
use App\Crm\Models\Tag;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class TagsController extends Controller
{
    public function index(): mixed
    {
        Gate::authorize('viewAny', Tag::class);

        return TagResource::collection(Tag::query()->orderBy('name')->get());
    }
}
