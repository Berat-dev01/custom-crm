<?php

namespace App\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Crm\Models\Concerns\HasPublicId;

class SavedFilter extends Model
{
    use HasPublicId;
    use SoftDeletes;

    protected $table = 'crm_saved_filters';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('visibility', 'public');

            if ($user) {
                $query->orWhere('user_id', $user->id);
            }
        });
    }
}
