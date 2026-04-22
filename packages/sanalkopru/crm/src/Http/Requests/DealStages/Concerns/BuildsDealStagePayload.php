<?php

namespace Sanalkopru\Crm\Http\Requests\DealStages\Concerns;

use Illuminate\Support\Str;

trait BuildsDealStagePayload
{
    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['is_won'] = (bool) ($validated['is_won'] ?? false);
        $validated['is_lost'] = (bool) ($validated['is_lost'] ?? false);

        return $validated;
    }
}
