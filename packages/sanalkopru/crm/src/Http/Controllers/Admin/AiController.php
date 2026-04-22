<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Sanalkopru\Crm\Models\Activity;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Services\Ai\AiAssistant;
use Sanalkopru\Crm\Support\Ai\AiResult;

class AiController extends Controller
{
    public function summarize(Request $request, AiAssistant $assistant): JsonResponse|RedirectResponse
    {
        Gate::authorize('crm.ai.use');

        $validated = $request->validate([
            'type' => ['nullable', 'string', Rule::in(['note', 'deal_timeline', 'lost_deal'])],
            'activity_id' => ['nullable', 'integer', 'exists:activities,id'],
            'deal_id' => ['required_if:type,deal_timeline,lost_deal', 'nullable', 'integer', 'exists:deals,id'],
            'content' => ['nullable', 'string', 'max:8000'],
        ]);

        $type = $validated['type'] ?? 'note';
        $result = match ($type) {
            'deal_timeline' => $assistant->summarizeDealTimeline(Deal::query()->findOrFail($validated['deal_id'])),
            'lost_deal' => $assistant->analyzeLostDeal(Deal::query()->findOrFail($validated['deal_id'])),
            default => $assistant->summarizeNote(
                isset($validated['activity_id']) ? Activity::query()->find($validated['activity_id']) : null,
                $validated['content'] ?? null
            ),
        };

        return $this->respond($request, $result, 'summary', 'crm_ai_summary');
    }

    public function summarizeNote(Request $request, AiAssistant $assistant): JsonResponse|RedirectResponse
    {
        $request->merge(['type' => 'note']);

        return $this->summarize($request, $assistant);
    }

    public function draftEmail(Request $request, AiAssistant $assistant): JsonResponse|RedirectResponse
    {
        Gate::authorize('crm.ai.use');

        $validated = $request->validate([
            'deal_id' => ['nullable', 'integer', 'exists:deals,id'],
            'deal_title' => ['nullable', 'string', 'max:255'],
            'brief' => ['nullable', 'string', 'max:4000'],
        ]);

        $deal = isset($validated['deal_id'])
            ? Deal::query()->find($validated['deal_id'])
            : null;
        $brief = $validated['brief'] ?? ('Draft a follow-up email for '.($validated['deal_title'] ?? 'this opportunity').'.');
        $result = $assistant->draftDealEmail($deal, $brief);

        return $this->respond($request, $result, 'draft', 'crm_ai_draft');
    }

    public function followUp(Request $request, AiAssistant $assistant): JsonResponse|RedirectResponse
    {
        Gate::authorize('crm.ai.use');

        $validated = $request->validate([
            'quote_id' => ['required', 'integer', 'exists:quotes,id'],
            'brief' => ['nullable', 'string', 'max:4000'],
        ]);

        $result = $assistant->draftQuoteFollowUp(
            Quote::query()->findOrFail($validated['quote_id']),
            $validated['brief'] ?? ''
        );

        return $this->respond($request, $result, 'draft', 'crm_ai_draft');
    }

    private function respond(Request $request, AiResult $result, string $key, string $sessionKey): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json($result->toArray($key), $result->status);
        }

        return back()
            ->with('crm_status', $result->ok ? 'AI draft prepared.' : ($result->message ?: 'AI request failed.'))
            ->with($sessionKey, $result->content);
    }
}
