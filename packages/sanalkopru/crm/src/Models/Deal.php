<?php

namespace Sanalkopru\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sanalkopru\Crm\Database\Factories\DealFactory;
use Sanalkopru\Crm\Models\Concerns\HasPublicId;

class Deal extends Model
{
    use HasFactory;
    use HasPublicId;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'probability' => 'integer',
            'position' => 'integer',
            'expected_close_date' => 'date',
            'closed_at' => 'datetime',
            'custom_fields' => 'array',
        ];
    }

    protected static function newFactory(): DealFactory
    {
        return DealFactory::new();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(DealStage::class, 'stage_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'activityable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'tag_relations');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeWon(Builder $query): Builder
    {
        return $query->where('status', 'won');
    }

    public function scopeLost(Builder $query): Builder
    {
        return $query->where('status', 'lost');
    }

    public function scopeForStage(Builder $query, int|string $stageId): Builder
    {
        return $query->where('stage_id', $stageId);
    }

    public function scopeOwnedBy(Builder $query, int|string|null $userId): Builder
    {
        return $userId ? $query->where('owner_id', $userId) : $query;
    }
}
