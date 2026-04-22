<?php

namespace Sanalkopru\Crm\Actions\DealStages;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Sanalkopru\Crm\Models\DealStage;

class ReorderDealStages
{
    /**
     * @param  list<array{id: int, position: int}>  $stages
     */
    public function handle(array $stages, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($stages, $user): void {
            foreach ($stages as $stage) {
                DealStage::query()
                    ->whereKey($stage['id'])
                    ->update([
                        'position' => $stage['position'],
                        'updated_by' => $user?->getAuthIdentifier(),
                        'updated_at' => now(),
                    ]);
            }
        });
    }
}
