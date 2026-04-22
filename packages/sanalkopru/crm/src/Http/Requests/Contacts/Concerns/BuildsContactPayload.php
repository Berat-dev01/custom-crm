<?php

namespace Sanalkopru\Crm\Http\Requests\Contacts\Concerns;

trait BuildsContactPayload
{
    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $validated = $this->validated();
        $customFields = json_decode((string) ($validated['custom_fields_json'] ?? ''), true);

        unset($validated['custom_fields_json'], $validated['tag_ids']);

        $validated['custom_fields'] = is_array($customFields) ? $customFields : null;
        $validated['tag_ids'] = $this->validated('tag_ids', []);

        return $validated;
    }
}
