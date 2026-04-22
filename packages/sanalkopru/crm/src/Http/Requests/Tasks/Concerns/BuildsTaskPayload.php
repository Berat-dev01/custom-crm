<?php

namespace Sanalkopru\Crm\Http\Requests\Tasks\Concerns;

use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\Quote;

trait BuildsTaskPayload
{
    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $taskableType = $validated['taskable_type'] ?? null;
        $taskableId = $validated['taskable_id'] ?? null;

        $validated['taskable_type'] = $taskableType ? $this->taskableClass($taskableType) : null;
        $validated['taskable_id'] = $taskableType ? $taskableId : null;

        return $validated;
    }

    private function taskableClass(string $type): string
    {
        return match ($type) {
            'contact' => Contact::class,
            'company' => Company::class,
            'deal' => Deal::class,
            'quote' => Quote::class,
        };
    }
}
