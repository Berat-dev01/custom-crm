<?php

namespace Sanalkopru\Crm\Actions\Deals;

use Illuminate\Contracts\Auth\Authenticatable;
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
            $deal->forceFill([
                'stage_id' => $stage->id,
                'position' => $position ?? $this->nextPosition($stage),
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

            return $deal->refresh();
        });
    }

    private function nextPosition(DealStage $stage): int
    {
        return ((int) Deal::query()->where('stage_id', $stage->id)->max('position')) + 1;
    }
}
