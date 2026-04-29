<?php

namespace App\Crm\Actions\Contacts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use App\Crm\Events\ContactCreated;
use App\Crm\Models\Contact;
use App\Crm\Services\Audit\CrmAuditLogger;

class UpsertContact
{
    public function __construct(private readonly CrmAuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Contact $contact, array $payload, ?Authenticatable $user = null): Contact
    {
        $tagIds = Arr::pull($payload, 'tag_ids', []);
        $isNew = ! $contact->exists;
        $before = $contact->exists ? $contact->only($this->auditedFields()) : [];

        $payload['full_name'] = $this->fullName($payload);
        $payload[$contact->exists ? 'updated_by' : 'created_by'] = $user?->getAuthIdentifier();

        $contact->fill($payload);
        $contact->save();

        $contact->tags()->sync($tagIds);

        if ($isNew) {
            event(new ContactCreated($contact->refresh(), $user));
            $this->audit->record('crm.contact.created', $contact, $user, null, $contact->only($this->auditedFields()));
        } else {
            $changes = $this->audit->diff($before, $contact->only($this->auditedFields()));

            if ($changes['new'] !== []) {
                $this->audit->record('crm.contact.updated', $contact, $user, $changes['old'], $changes['new']);
            }
        }

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

    /**
     * @return list<string>
     */
    private function auditedFields(): array
    {
        return [
            'first_name',
            'last_name',
            'full_name',
            'email',
            'phone',
            'title',
            'company_id',
            'lifecycle_stage',
            'source',
            'owner_id',
            'last_contacted_at',
        ];
    }
}
