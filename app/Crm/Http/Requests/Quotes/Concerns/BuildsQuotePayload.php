<?php

namespace App\Crm\Http\Requests\Quotes\Concerns;

use App\Crm\Services\Configuration\MoneySettings;

trait BuildsQuotePayload
{
    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $validated['items'] = collect($validated['items'] ?? [])
            ->values()
            ->map(function (array $item, int $index): array {
                $item['position'] = (int) ($item['position'] ?? ($index + 1));
                $item['discount_type'] = $item['discount_type'] ?? null;
                $item['discount_value'] = $item['discount_value'] ?? 0;
                $item['tax_rate'] = $item['tax_rate'] ?? app(MoneySettings::class)->defaultTaxRate();

                return $item;
            })
            ->sortBy('position')
            ->values()
            ->all();
        $validated['tag_ids'] = $validated['tag_ids'] ?? [];
        $validated['discount_type'] = $validated['discount_type'] ?? null;
        $validated['discount_value'] = $validated['discount_value'] ?? 0;
        $validated['terms'] = $validated['terms'] ?? app(MoneySettings::class)->quoteTerms();

        return $validated;
    }
}
