<?php

namespace App\Crm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Crm\Database\Factories\DealStageFactory;
use App\Crm\Models\Concerns\HasPublicId;

class DealStage extends Model
{
    use HasFactory;
    use HasPublicId;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
            'probability' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function newFactory(): DealStageFactory
    {
        return DealStageFactory::new();
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'stage_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
