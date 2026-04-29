<?php

namespace App\Crm\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Crm\Database\Factories\TaskFactory;
use App\Crm\Models\Concerns\HasPublicId;

class Task extends Model
{
    use HasFactory;
    use HasPublicId;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'reminder_at' => 'datetime',
            'reminder_notified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addDays($days)]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('completed_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNull('completed_at');
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->whereNotNull('due_at')
            ->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]);
    }
}
