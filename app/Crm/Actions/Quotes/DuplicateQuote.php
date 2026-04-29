<?php

namespace App\Crm\Actions\Quotes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use App\Crm\Models\Quote;
use App\Crm\Services\Quotes\QuoteNumberGenerator;

class DuplicateQuote
{
    public function __construct(private readonly QuoteNumberGenerator $numbers) {}

    public function handle(Quote $quote, ?Authenticatable $user = null): Quote
    {
        return DB::transaction(function () use ($quote, $user): Quote {
            $newQuote = $quote->replicate([
                'public_id',
                'quote_number',
                'status',
                'sent_at',
                'accepted_at',
                'rejected_at',
            ]);
            $newQuote->forceFill([
                'quote_number' => $this->numbers->next(),
                'status' => 'draft',
                'sent_at' => null,
                'accepted_at' => null,
                'rejected_at' => null,
                'created_by' => $user?->getAuthIdentifier(),
                'updated_by' => $user?->getAuthIdentifier(),
            ]);
            $newQuote->save();

            foreach ($quote->items()->orderBy('position')->get() as $item) {
                $newItem = $item->replicate(['public_id', 'quote_id']);
                $newItem->forceFill([
                    'quote_id' => $newQuote->id,
                    'created_by' => $user?->getAuthIdentifier(),
                    'updated_by' => $user?->getAuthIdentifier(),
                ]);
                $newItem->save();
            }

            $newQuote->tags()->sync($quote->tags()->pluck('tags.id')->all());

            return $newQuote->refresh();
        });
    }
}
