<?php

namespace App\Crm\Models;

use App\Crm\Models\Concerns\HasPublicId;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmWebhook extends Model
{
    use HasPublicId;
    use SoftDeletes;

    /**
     * Events a webhook can subscribe to.
     */
    public const EVENTS = [
        'contact.created',
        'company.created',
        'deal.created',
        'deal.won',
        'deal.lost',
        'quote.sent',
        'quote.accepted',
        'quote.rejected',
        'task.completed',
    ];

    protected $table = 'crm_webhooks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(CrmWebhookDelivery::class, 'webhook_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSubscribedTo(Builder $query, string $event): Builder
    {
        return $query->whereJsonContains('events', $event);
    }

    public function signature(string $body): string
    {
        return hash_hmac('sha256', $body, $this->secret);
    }
}
