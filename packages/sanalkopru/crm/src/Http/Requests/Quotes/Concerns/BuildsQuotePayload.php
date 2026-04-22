<?php

namespace Sanalkopru\Crm\Http\Requests\Quotes\Concerns;

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
                $item['tax_rate'] = $item['tax_rate'] ?? config('crm.money.default_tax_rate', 20);

                return $item;
            })
            ->sortBy('position')
            ->values()
            ->all();
        $validated['tag_ids'] = $validated['tag_ids'] ?? [];
        $validated['discount_type'] = $validated['discount_type'] ?? null;
        $validated['discount_value'] = $validated['discount_value'] ?? 0;

        return $validated;
    }
}
