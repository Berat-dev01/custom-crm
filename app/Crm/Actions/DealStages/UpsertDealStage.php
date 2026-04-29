<?php

namespace App\Crm\Actions\DealStages;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use App\Crm\Models\DealStage;

class UpsertDealStage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(DealStage $stage, array $payload, ?Authenticatable $user = null): DealStage
    {
        return DB::transaction(function () use ($stage, $payload, $user): DealStage {
            if ($payload['is_won'] ?? false) {
                DealStage::query()->whereKeyNot($stage->id)->update(['is_won' => false]);
                $payload['probability'] = 100;
                $payload['is_lost'] = false;
            }

            if ($payload['is_lost'] ?? false) {
                DealStage::query()->whereKeyNot($stage->id)->update(['is_lost' => false]);
                $payload['probability'] = 0;
                $payload['is_won'] = false;
            }

            $payload[$stage->exists ? 'updated_by' : 'created_by'] = $user?->getAuthIdentifier();

            $stage->fill($payload);
            $stage->save();

            return $stage->refresh();
        });
    }
}
