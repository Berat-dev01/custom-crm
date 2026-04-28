<?php

namespace Sanalkopru\Crm\Actions\DealStages;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Sanalkopru\Crm\Actions\Deals\MoveDealToStage;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;

class DeleteDealStage
{
    public function __construct(private readonly MoveDealToStage $moveDeal) {}

    public function handle(DealStage $stage, int|string|null $replacementStageId, ?Authenticatable $user = null): void
    {
        $deals = $stage->deals()->get();

        if ($deals->isNotEmpty() && ! $replacementStageId) {
            throw ValidationException::withMessages([
                'replacement_stage_id' => trans('crm::messages.deal_stages.replacement_required'),
            ]);
        }

        if ($replacementStageId) {
            $replacement = DealStage::query()->findOrFail($replacementStageId);

            $deals->each(fn (Deal $deal) => $this->moveDeal->handle($deal, $replacement, null, null, $user));
        }

        $stage->delete();
    }
}
