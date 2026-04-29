<?php

namespace App\Crm\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Crm\Database\Factories\QuoteItemFactory;
use App\Crm\Models\Concerns\HasPublicId;

class QuoteItem extends Model
{
    use HasFactory;
    use HasPublicId;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'line_total' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    protected static function newFactory(): QuoteItemFactory
    {
        return QuoteItemFactory::new();
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
