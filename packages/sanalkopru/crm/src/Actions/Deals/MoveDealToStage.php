<?php

namespace Sanalkopru\Crm\Actions\Deals;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;

class MoveDealToStage
{
    public function handle(
        Deal $deal,
        DealStage $stage,
        ?int $position = null,
        ?string $lostReason = null,
        ?Authenticatable $user = null
    ): Deal {
        return DB::transaction(function () use ($deal, $stage, $position, $lostReason, $user): Deal {
            $deal = Deal::query()->lockForUpdate()->findOrFail($deal->id);
            $sourceStageId = $deal->stage_id;

            $this->lockStageDeals($sourceStageId);
            if ((int) $sourceStageId !== (int) $stage->id) {
                $this->lockStageDeals($stage->id);
            }

            $deal->forceFill([
                'stage_id' => $stage->id,
                'probability' => $stage->probability,
                'updated_by' => $user?->getAuthIdentifier(),
            ]);

            if ($stage->is_won) {
                $deal->forceFill([
                    'status' => 'won',
                    'closed_at' => $deal->closed_at ?: now(),
                    'lost_reason' => null,
                ]);
            } elseif ($stage->is_lost) {
                $deal->forceFill([
                    'status' => 'lost',
                    'closed_at' => $deal->closed_at ?: now(),
                    'lost_reason' => $lostReason,
                ]);
            } else {
                $deal->forceFill([
                    'status' => 'open',
                    'closed_at' => null,
                    'lost_reason' => null,
                ]);
            }

            $deal->save();
            $this->normalizeStagePositions($stage->id, $deal, $position);

            if ((int) $sourceStageId !== (int) $stage->id) {
                $this->normalizeStagePositions($sourceStageId);
            }

            return $deal->refresh();
        });
    }

    private function lockStageDeals(int|string|null $stageId): void
    {
        if (! $stageId) {
            return;
        }

        Deal::query()
            ->where('stage_id', $stageId)
            ->orderBy('position')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    private function normalizeStagePositions(int|string|null $stageId, ?Deal $pinnedDeal = null, ?int $position = null): void
    {
        if (! $stageId) {
            return;
        }

        $dealIds = Deal::query()
            ->where('stage_id', $stageId)
            ->when($pinnedDeal, fn ($query) => $query->whereKeyNot($pinnedDeal->id))
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        if ($pinnedDeal) {
            $dealIds = $this->insertPinnedDeal($dealIds, $pinnedDeal->id, $position);
        }

        foreach ($dealIds as $index => $dealId) {
            Deal::query()
                ->whereKey($dealId)
                ->update([
                    'position' => $index + 1,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * @param  Collection<int, int>  $dealIds
     * @return Collection<int, int>
     */
    private function insertPinnedDeal(Collection $dealIds, int $dealId, ?int $position): Collection
    {
        $index = max(0, min(($position ?? ($dealIds->count() + 1)) - 1, $dealIds->count()));
        $items = $dealIds->all();
        array_splice($items, $index, 0, [$dealId]);

        return collect($items)->values();
    }
}
