<?php

namespace App\Crm\Models\Concerns;

use Illuminate\Support\Str;

trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function (self $model): void {
            if (! $model->public_id) {
                $model->public_id = (string) Str::uuid();
            }
        });
    }
}
