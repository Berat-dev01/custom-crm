<?php

namespace App\Crm\Actions\Deals;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Crm\Events\DealMoved;
use App\Crm\Models\Deal;
use App\Crm\Models\DealStage;
use App\Crm\Services\Audit\CrmAuditLogger;
use App\Crm\Services\Notifications\CrmBusinessNotifier;
use App\Crm\Services\Webhooks\CrmWebhookDispatcher;

class MoveDealToStage
{
    public function __construct(
        private readonly CrmAuditLogger $audit,
        private readonly CrmBusinessNotifier $notifications,
        private readonly CrmWebhookDispatcher $webhooks
    ) {}

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
            $sourceStage = DealStage::query()->find($sourceStageId);
            $before = $deal->only($this->auditedFields());

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

            $deal = $deal->refresh();
            event(new DealMoved($deal, $sourceStage, $stage, $user));
            $event = match ($deal->status) {
                'won' => 'crm.deal.won',
                'lost' => 'crm.deal.lost',
                default => 'crm.deal.moved',
            };
            $this->audit->record(
                $event,
                $deal,
                $user,
                $before,
                $deal->only($this->auditedFields()),
                [
                    'from_stage_id' => $sourceStage?->id,
                    'from_stage' => $sourceStage?->name,
                    'to_stage_id' => $stage->id,
                    'to_stage' => $stage->name,
                ]
            );

            if (in_array($deal->status, ['won', 'lost'], true) && ($before['status'] ?? null) !== $deal->status) {
                $this->notifications->dealClosed($deal, $deal->status, $user);
                $this->webhooks->dispatch('deal.'.$deal->status, $deal);
            }

            return $deal;
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

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'stage_id',
            'status',
            'probability',
            'position',
            'closed_at',
            'lost_reason',
        ];
    }
}
