<?php

namespace App\Crm\Services\Contacts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Crm\Actions\Contacts\UpsertContact;
use App\Crm\Models\Contact;

class ContactImportService
{
    public function __construct(private readonly UpsertContact $upsert) {}

    /**
     * @return array{created: int, failed: int, errors: list<array{row: int, message: string}>}
     */
    public function import(UploadedFile $file, ?Authenticatable $user = null): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        $headers = $this->headers((array) fgetcsv($handle));
        $created = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $payload = $this->payload($headers, $row);
            $validator = Validator::make($payload, $this->rules());

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $validator->errors()->first(),
                ];

                continue;
            }

            $this->upsert->handle(new Contact, $validator->validated(), $user);
            $created++;
        }

        fclose($handle);

        return [
            'created' => $created,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<string>  $row
     * @return list<string>
     */
    private function headers(array $row): array
    {
        return collect($row)
            ->map(fn (?string $header): string => Str::of((string) $header)->trim()->lower()->snake()->toString())
            ->all();
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string>  $row
     * @return array<string, mixed>
     */
    private function payload(array $headers, array $row): array
    {
        $payload = [];

        foreach ($headers as $index => $header) {
            $payload[$header] = $row[$index] ?? null;
        }

        $payload['full_name'] = $payload['full_name'] ?? trim(($payload['first_name'] ?? '').' '.($payload['last_name'] ?? ''));
        $payload['lifecycle_stage'] = $payload['lifecycle_stage'] ?: 'lead';
        $payload['tag_ids'] = [];

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'full_name' => ['required_without:first_name', 'nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('contacts', 'email')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:160'],
            'lifecycle_stage' => ['required', 'string', 'max:40'],
            'source' => ['nullable', 'string', 'max:80'],
            'tag_ids' => ['array'],
        ];
    }
}
