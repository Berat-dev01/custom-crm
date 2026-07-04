<?php

namespace App\Crm\Services\Contacts;

use App\Crm\Models\Contact;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactCsvExporter
{
    /**
     * @param  Collection<int, Contact>  $contacts
     */
    public function stream(Collection $contacts): StreamedResponse
    {
        return response()->streamDownload(function () use ($contacts): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'full_name',
                'first_name',
                'last_name',
                'email',
                'phone',
                'title',
                'company',
                'lifecycle_stage',
                'source',
                'owner',
                'tags',
                'last_contacted_at',
            ]);

            $contacts->each(function (Contact $contact) use ($handle): void {
                fputcsv($handle, [
                    $contact->full_name,
                    $contact->first_name,
                    $contact->last_name,
                    $contact->email,
                    $contact->phone,
                    $contact->title,
                    $contact->company?->name,
                    $contact->lifecycle_stage,
                    $contact->source,
                    $contact->owner?->name,
                    $contact->tags->pluck('name')->implode('|'),
                    $contact->last_contacted_at?->toDateTimeString(),
                ]);
            });

            fclose($handle);
        }, 'crm-contacts-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
