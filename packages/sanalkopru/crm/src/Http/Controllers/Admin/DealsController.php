<?php

namespace Sanalkopru\Crm\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Sanalkopru\Crm\Actions\Deals\MoveDealToStage;
use Sanalkopru\Crm\Http\Requests\Deals\MoveDealRequest;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;

class DealsController extends CrmResourceController
{
    protected string $module = 'deals';

    protected string $title = 'Deals';

    protected string $permissionPrefix = 'crm.deals';

    public function move(MoveDealRequest $request, Deal $deal, MoveDealToStage $moveDeal): JsonResponse
    {
        $stage = DealStage::query()->findOrFail($request->validated('stage_id'));
        $deal = $moveDeal->handle(
            $deal,
            $stage,
            $request->integer('position') ?: null,
            $request->validated('lost_reason'),
            $request->user()
        );

        return response()->json([
            'message' => 'Deal moved.',
            'deal' => [
                'id' => $deal->id,
                'stage_id' => $deal->stage_id,
                'status' => $deal->status,
                'position' => $deal->position,
                'probability' => $deal->probability,
                'closed_at' => $deal->closed_at?->toISOString(),
                'lost_reason' => $deal->lost_reason,
            ],
        ]);
    }
}
