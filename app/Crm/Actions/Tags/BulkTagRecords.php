<?php

namespace App\Crm\Actions\Tags;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use App\Crm\Models\Company;
use App\Crm\Models\Contact;
use App\Crm\Models\Deal;
use App\Crm\Models\Quote;

class BulkTagRecords
{
    /**
     * @param  list<int>  $recordIds
     * @param  list<int>  $tagIds
     */
    public function handle(string $type, array $recordIds, array $tagIds, string $mode, ?Authenticatable $user = null): int
    {
        $count = 0;

        $this->modelClass($type)::query()
            ->whereKey($recordIds)
            ->get()
            ->each(function (Model $model) use ($tagIds, $mode, &$count): void {
                if ($mode === 'detach') {
                    $model->tags()->detach($tagIds);
                } else {
                    $model->tags()->syncWithoutDetaching($tagIds);
                }

                $count++;
            });

        return $count;
    }

    private function modelClass(string $type): string
    {
        return match ($type) {
            'contact' => Contact::class,
            'company' => Company::class,
            'deal' => Deal::class,
            'quote' => Quote::class,
        };
    }
}
