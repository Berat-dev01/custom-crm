<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AiController extends Controller
{
    public function summarizeNote(): JsonResponse
    {
        Gate::authorize('crm.ai.use');

        return response()->json([
            'message' => 'AI note summarization endpoint is registered and awaits action implementation.',
        ], Response::HTTP_ACCEPTED);
    }

    public function draftEmail(): JsonResponse
    {
        Gate::authorize('crm.ai.use');

        return response()->json([
            'message' => 'AI email draft endpoint is registered and awaits action implementation.',
        ], Response::HTTP_ACCEPTED);
    }
}
