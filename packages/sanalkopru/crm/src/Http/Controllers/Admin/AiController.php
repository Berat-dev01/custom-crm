<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function draftEmail(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('crm.ai.use');

        if (! $request->expectsJson()) {
            return back()
                ->with('crm_status', 'AI email draft prepared as a placeholder. Driver execution will be completed in the AI module step.')
                ->with('crm_ai_draft', "Subject: Following up on {$request->input('deal_title', 'your opportunity')}\n\nHello,\n\nI wanted to follow up with a clear next step and make sure we are aligned on timing, scope and decision criteria.\n\nBest regards,");
        }

        return response()->json([
            'message' => 'AI email draft endpoint is registered and awaits action implementation.',
        ], Response::HTTP_ACCEPTED);
    }
}
