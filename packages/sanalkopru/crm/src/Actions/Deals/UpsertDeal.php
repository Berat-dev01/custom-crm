<?php

namespace Sanalkopru\Crm\Actions\Deals;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;

class UpsertDeal
{
    public function __construct(private readonly MoveDealToStage $moveDeal) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Deal $deal, array $payload, ?Authenticatable $user = null): Deal
    {
        return DB::transaction(function () use ($deal, $payload, $user): Deal {
            $tagIds = Arr::pull($payload, 'tag_ids', []);
            $stageId = (int) Arr::pull($payload, 'stage_id');
            $lostReason = Arr::pull($payload, 'lost_reason');

            $payload[$deal->exists ? 'updated_by' : 'created_by'] = $user?->getAuthIdentifier();

            $deal->fill($payload);

            if (! $deal->exists) {
                $deal->stage_id = $stageId;
                $deal->position = ((int) Deal::query()->where('stage_id', $stageId)->max('position')) + 1;
            }

            $deal->save();
            $deal->tags()->sync($tagIds);

            if ((int) $deal->stage_id !== $stageId || $this->stageControlsStatus($stageId)) {
                $deal = $this->moveDeal->handle($deal, DealStage::query()->findOrFail($stageId), null, $lostReason, $user);
            }

            return $deal->refresh();
        });
    }

    private function stageControlsStatus(int $stageId): bool
    {
        $stage = DealStage::query()->find($stageId);

        return (bool) ($stage?->is_won || $stage?->is_lost);
    }
}
