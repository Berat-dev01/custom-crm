<?php

namespace App\Crm\Models;

use App\Crm\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmWebhookDelivery extends Model
{
    use HasPublicId;

    protected $table = 'crm_webhook_deliveries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(CrmWebhook::class, 'webhook_id');
    }
}
