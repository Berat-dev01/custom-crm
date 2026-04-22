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
use Sanalkopru\Crm\Database\Factories\QuoteFactory;
use Sanalkopru\Crm\Models\Concerns\HasPublicId;

class Quote extends Model
{
    use HasFactory;
    use HasPublicId;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    protected static function newFactory(): QuoteFactory
    {
        return QuoteFactory::new();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('position')->orderBy('id');
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['draft', 'sent'])
            ->where(function (Builder $query): void {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            });
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }
}
