<?php

namespace Sanalkopru\Crm\Actions\Contacts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Sanalkopru\Crm\Models\Contact;

class UpsertContact
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Contact $contact, array $payload, ?Authenticatable $user = null): Contact
    {
        $tagIds = Arr::pull($payload, 'tag_ids', []);

        $payload['full_name'] = $this->fullName($payload);
        $payload[$contact->exists ? 'updated_by' : 'created_by'] = $user?->getAuthIdentifier();

        $contact->fill($payload);
        $contact->save();

        $contact->tags()->sync($tagIds);

        return $contact->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fullName(array $payload): string
    {
        $fullName = trim((string) ($payload['full_name'] ?? ''));

        if ($fullName !== '') {
            return $fullName;
        }

        return trim(sprintf('%s %s', $payload['first_name'] ?? '', $payload['last_name'] ?? ''));
    }
}
