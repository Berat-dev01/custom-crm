<?php

namespace Sanalkopru\Crm\Services\DataTransfer;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use Sanalkopru\Crm\Actions\Companies\UpsertCompany;
use Sanalkopru\Crm\Actions\Contacts\UpsertContact;
use Sanalkopru\Crm\Actions\Deals\UpsertDeal;
use Sanalkopru\Crm\Jobs\ProcessCrmImport;
use Sanalkopru\Crm\Models\Company;
use Sanalkopru\Crm\Models\Contact;
use Sanalkopru\Crm\Models\CrmExport;
use Sanalkopru\Crm\Models\CrmImport;
use Sanalkopru\Crm\Models\Deal;
use Sanalkopru\Crm\Models\DealStage;
use Sanalkopru\Crm\Models\Quote;
use Sanalkopru\Crm\Services\Audit\CrmAuditLogger;
use Sanalkopru\Crm\Services\Configuration\MoneySettings;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmDataTransferService
{
    public const IMPORT_MODULES = ['contacts', 'companies', 'deals'];

    public const EXPORT_MODULES = ['contacts', 'companies', 'deals', 'quotes'];

    public function __construct(
        private readonly TabularFileReader $reader,
        private readonly UpsertContact $upsertContact,
        private readonly UpsertCompany $upsertCompany,
        private readonly UpsertDeal $upsertDeal,
        private readonly MoneySettings $money,
        private readonly CrmAuditLogger $audit
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function preview(string $module, UploadedFile $file): array
    {
        $this->ensureImportModule($module);

        $rawPreview = $this->reader->preview($file);
        $expectedHeaders = $this->templateHeaders($module);
        $rows = [];
        $validRows = 0;
        $invalidRows = 0;

        foreach ($rawPreview['rows'] as $index => $payload) {
            $prepared = $this->preparePayload($module, $payload);
            $validator = Validator::make($prepared, $this->rules($module));
            $errors = $validator->errors()->all();

            if ($errors === []) {
                $validRows++;
            } else {
                $invalidRows++;
            }

            $rows[] = [
                'row' => $index + 2,
                'values' => $payload,
                'prepared' => $prepared,
                'valid' => $errors === [],
                'errors' => $errors,
            ];
        }

        return [
            'headers' => $rawPreview['headers'],
            'expected_headers' => $expectedHeaders,
            'missing_headers' => array_values(array_diff($expectedHeaders, $rawPreview['headers'])),
            'unexpected_headers' => array_values(array_diff($rawPreview['headers'], $expectedHeaders)),
            'rows' => $rows,
            'total_rows' => $rawPreview['total_rows'],
            'summary' => [
                'shown_rows' => count($rows),
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'total_rows' => $rawPreview['total_rows'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $module, UploadedFile $file, ?Authenticatable $user = null): array
    {
        $this->ensureImportModule($module);

        $disk = config('crm.data_transfer.disk', 'local');
        $extension = $file->getClientOriginalExtension() ?: 'csv';
        $path = $file->storeAs(
            "crm/imports/{$module}",
            now()->format('YmdHis').'-'.Str::random(10).'.'.$extension,
            $disk
        );

        $totalRows = $this->reader->countDataRows(Storage::disk($disk)->path($path), $extension);
        $import = CrmImport::query()->create([
            'module' => $module,
            'filename' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'status' => 'pending',
            'total_rows' => $totalRows,
            'created_by' => $user?->getAuthIdentifier(),
            'options' => [
                'extension' => $extension,
            ],
        ]);
        $this->audit->record('crm.import.started', $import, $user, null, [
            'module' => $module,
            'total_rows' => $totalRows,
            'queued' => $totalRows > (int) config('crm.data_transfer.queue_threshold', 500),
        ], [
            'extension' => $extension,
        ]);

        if ($totalRows > (int) config('crm.data_transfer.queue_threshold', 500)) {
            ProcessCrmImport::dispatch($import->id, $user?->getAuthIdentifier());

            return [
                'import_id' => $import->public_id,
                'module' => $import->module,
                'queued' => true,
                'created' => 0,
                'failed' => 0,
                'errors' => [],
                'error_report_url' => null,
            ];
        }

        return $this->process($import, $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function process(CrmImport $import, ?Authenticatable $user = null): array
    {
        $this->ensureImportModule($import->module);

        $disk = $import->disk ?: config('crm.data_transfer.disk', 'local');
        $path = Storage::disk($disk)->path((string) $import->path);
        $extension = data_get($import->options, 'extension', pathinfo($path, PATHINFO_EXTENSION));
        $headers = [];
        $created = 0;
        $errors = [];
        $seen = [];

        $import->forceFill([
            'status' => 'processing',
            'started_at' => now(),
        ])->save();

        foreach ($this->reader->rows($path, $extension) as $rowNumber => $row) {
            if ($rowNumber === 1) {
                $headers = $this->reader->headers($row);

                continue;
            }

            if ($this->reader->blankRow($row)) {
                continue;
            }

            $payload = $this->reader->combine($headers, $row);
            $prepared = $this->preparePayload($import->module, $payload);
            $duplicate = $this->duplicateMessage($import->module, $prepared, $seen);

            if ($duplicate) {
                $errors[] = $this->error($rowNumber, $duplicate, $payload);

                continue;
            }

            $validator = Validator::make($prepared, $this->rules($import->module));

            if ($validator->fails()) {
                $errors[] = $this->error($rowNumber, (string) $validator->errors()->first(), $payload);

                continue;
            }

            $this->storeRow($import->module, $validator->validated(), $user);
            $created++;
        }

        $reportPath = $this->writeErrorReport($import, $errors);

        $import->forceFill([
            'status' => count($errors) > 0 ? 'completed_with_errors' : 'completed',
            'processed_rows' => $created,
            'failed_rows' => count($errors),
            'error_report_path' => $reportPath,
            'finished_at' => now(),
        ])->save();

        return [
            'import_id' => $import->public_id,
            'module' => $import->module,
            'queued' => false,
            'created' => $created,
            'failed' => count($errors),
            'errors' => $errors,
            'error_report_url' => $reportPath ? route('crm.imports.errors', $import->public_id) : null,
        ];
    }

    public function streamTemplate(string $module): StreamedResponse
    {
        $this->ensureImportModule($module);

        return $this->streamCsv(
            'crm-'.$module.'-template.csv',
            $this->templateHeaders($module),
            [$this->templateSample($module)]
        );
    }

    public function streamExport(string $module, Request $request, ?Authenticatable $user = null): StreamedResponse
    {
        $this->ensureExportModule($module);

        $rows = $this->exportRows($module, $request);
        $headers = $this->exportHeaders($module);

        $export = CrmExport::query()->create([
            'module' => $module,
            'filename' => 'crm-'.$module.'-'.now()->format('Y-m-d-His').'.csv',
            'status' => 'completed',
            'total_rows' => $rows->count(),
            'filters' => $request->query(),
            'started_at' => now(),
            'finished_at' => now(),
            'created_by' => $user?->getAuthIdentifier(),
        ]);
        $this->audit->record('crm.export.started', $export, $user, null, [
            'module' => $module,
            'total_rows' => $rows->count(),
        ], [
            'filters' => $request->query(),
        ]);

        return $this->streamCsv('crm-'.$module.'-'.now()->format('Y-m-d-His').'.csv', $headers, $rows->all());
    }

    public function downloadErrorReport(CrmImport $import): StreamedResponse
    {
        abort_unless($import->error_report_path, 404);

        $disk = $import->disk ?: config('crm.data_transfer.disk', 'local');
        abort_unless(Storage::disk($disk)->exists($import->error_report_path), 404);

        return Storage::disk($disk)->download($import->error_report_path, 'crm-'.$import->module.'-import-errors.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return list<string>
     */
    public function templateHeaders(string $module): array
    {
        return match ($module) {
            'contacts' => ['full_name', 'first_name', 'last_name', 'email', 'phone', 'title', 'company', 'lifecycle_stage', 'source', 'owner_email', 'tags'],
            'companies' => ['name', 'email', 'phone', 'website', 'tax_number', 'tax_office', 'sector', 'address_line_1', 'city', 'state', 'postal_code', 'country', 'owner_email', 'tags'],
            'deals' => ['title', 'company', 'contact_email', 'stage', 'value', 'currency', 'probability', 'expected_close_date', 'status', 'lost_reason', 'owner_email', 'tags'],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function templateSample(string $module): array
    {
        return match ($module) {
            'contacts' => ['Ada Lovelace', 'Ada', 'Lovelace', 'ada@example.com', '+905551112233', 'CTO', 'Acme A.S.', 'lead', 'website', 'sales@example.com', 'VIP|Enterprise'],
            'companies' => ['Acme A.S.', 'info@acme.test', '+902121112233', 'https://acme.test', '1234567890', 'Besiktas', 'Technology', 'Buyukdere Cad. No:1', 'Istanbul', 'Istanbul', '34000', 'TR', 'sales@example.com', 'Enterprise'],
            'deals' => ['Acme CRM Rollout', 'Acme A.S.', 'ada@example.com', 'Qualified', '250000', 'TRY', '60', now()->addMonth()->toDateString(), 'open', '', 'sales@example.com', 'Enterprise'],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function exportHeaders(string $module): array
    {
        return match ($module) {
            'contacts' => ['full_name', 'first_name', 'last_name', 'email', 'phone', 'title', 'company', 'lifecycle_stage', 'source', 'owner', 'tags', 'last_contacted_at'],
            'companies' => ['name', 'email', 'phone', 'website', 'tax_number', 'tax_office', 'sector', 'city', 'country', 'owner', 'tags', 'contacts_count', 'deals_count', 'quotes_count'],
            'deals' => ['title', 'company', 'contact', 'stage', 'status', 'value', 'currency', 'probability', 'expected_close_date', 'owner', 'tags', 'lost_reason'],
            'quotes' => ['quote_number', 'company', 'contact', 'deal', 'status', 'currency', 'subtotal', 'discount_total', 'tax_total', 'grand_total', 'valid_until', 'owner', 'tags'],
            default => [],
        };
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int|string, mixed>>  $rows
     */
    private function streamCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $writer = new CsvWriter;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($headers));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_values($row)));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function preparePayload(string $module, array $payload): array
    {
        return match ($module) {
            'contacts' => $this->contactPayload($payload),
            'companies' => $this->companyPayload($payload),
            'deals' => $this->dealPayload($payload),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function contactPayload(array $payload): array
    {
        $company = $this->companyByName($this->payloadValue($payload, 'company'));
        $firstName = $this->payloadValue($payload, 'first_name');
        $lastName = $this->payloadValue($payload, 'last_name');

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $this->payloadValue($payload, 'full_name') ?: trim($firstName.' '.$lastName),
            'email' => $this->payloadValue($payload, 'email'),
            'phone' => $this->payloadValue($payload, 'phone'),
            'title' => $this->payloadValue($payload, 'title'),
            'company_id' => $company?->id,
            'lifecycle_stage' => $this->payloadValue($payload, 'lifecycle_stage', 'lead'),
            'source' => $this->payloadValue($payload, 'source'),
            'owner_id' => $this->userIdByEmail($this->payloadValue($payload, 'owner_email')),
            'tag_ids' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function companyPayload(array $payload): array
    {
        return [
            'name' => $this->payloadValue($payload, 'name'),
            'email' => $this->payloadValue($payload, 'email'),
            'phone' => $this->payloadValue($payload, 'phone'),
            'website' => $this->payloadValue($payload, 'website'),
            'tax_number' => $this->payloadValue($payload, 'tax_number'),
            'tax_office' => $this->payloadValue($payload, 'tax_office'),
            'sector' => $this->payloadValue($payload, 'sector'),
            'address_line_1' => $this->payloadValue($payload, 'address_line_1'),
            'city' => $this->payloadValue($payload, 'city'),
            'state' => $this->payloadValue($payload, 'state'),
            'postal_code' => $this->payloadValue($payload, 'postal_code'),
            'country' => $this->payloadValue($payload, 'country'),
            'owner_id' => $this->userIdByEmail($this->payloadValue($payload, 'owner_email')),
            'tag_ids' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dealPayload(array $payload): array
    {
        $stage = $this->stageByName($this->payloadValue($payload, 'stage')) ?: DealStage::query()->ordered()->first();
        $company = $this->companyByName($this->payloadValue($payload, 'company'));
        $contact = $this->contactByEmail($this->payloadValue($payload, 'contact_email'));

        return [
            'title' => $this->payloadValue($payload, 'title'),
            'company_id' => $company?->id,
            'contact_id' => $contact?->id,
            'stage_id' => $stage?->id,
            'value' => $this->payloadValue($payload, 'value', '0'),
            'currency' => $this->payloadValue($payload, 'currency', $this->money->defaultCurrency()),
            'probability' => $this->payloadValue($payload, 'probability', (string) ($stage?->probability ?? 0)),
            'expected_close_date' => $this->payloadValue($payload, 'expected_close_date'),
            'status' => $this->payloadValue($payload, 'status', 'open'),
            'lost_reason' => $this->payloadValue($payload, 'lost_reason'),
            'owner_id' => $this->userIdByEmail($this->payloadValue($payload, 'owner_email')),
            'tag_ids' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(string $module): array
    {
        return match ($module) {
            'contacts' => [
                'first_name' => ['nullable', 'string', 'max:120'],
                'last_name' => ['nullable', 'string', 'max:120'],
                'full_name' => ['required_without:first_name', 'nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique('contacts', 'email')->whereNull('deleted_at')],
                'phone' => ['nullable', 'string', 'max:50'],
                'title' => ['nullable', 'string', 'max:160'],
                'company_id' => ['nullable', 'integer', 'exists:companies,id'],
                'lifecycle_stage' => ['required', 'string', 'max:40'],
                'source' => ['nullable', 'string', 'max:80'],
                'owner_id' => ['nullable', 'integer', 'exists:users,id'],
                'tag_ids' => ['array'],
            ],
            'companies' => [
                'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')->whereNull('deleted_at')],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'website' => ['nullable', 'url', 'max:255'],
                'tax_number' => ['nullable', 'string', 'max:80', Rule::unique('companies', 'tax_number')->whereNull('deleted_at')],
                'tax_office' => ['nullable', 'string', 'max:120'],
                'sector' => ['nullable', 'string', 'max:120'],
                'address_line_1' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:120'],
                'state' => ['nullable', 'string', 'max:120'],
                'postal_code' => ['nullable', 'string', 'max:40'],
                'country' => ['nullable', 'string', 'max:80'],
                'owner_id' => ['nullable', 'integer', 'exists:users,id'],
                'tag_ids' => ['array'],
            ],
            'deals' => [
                'title' => ['required', 'string', 'max:255'],
                'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
                'company_id' => ['nullable', 'integer', 'exists:companies,id'],
                'stage_id' => ['required', 'integer', 'exists:deal_stages,id'],
                'value' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
                'currency' => ['required', 'string', 'size:3'],
                'probability' => ['required', 'integer', 'min:0', 'max:100'],
                'expected_close_date' => ['nullable', 'date'],
                'status' => ['required', 'string', 'in:open,won,lost'],
                'lost_reason' => ['nullable', 'string', 'max:255'],
                'owner_id' => ['nullable', 'integer', 'exists:users,id'],
                'tag_ids' => ['array'],
            ],
            default => [],
        };
    }

    private function storeRow(string $module, array $payload, ?Authenticatable $user): void
    {
        match ($module) {
            'contacts' => $this->upsertContact->handle(new Contact, $payload, $user),
            'companies' => $this->upsertCompany->handle(new Company, $payload, $user),
            'deals' => $this->upsertDeal->handle(new Deal, $payload, $user),
            default => null,
        };
    }

    /**
     * @param  array<string, list<string>>  $seen
     */
    private function duplicateMessage(string $module, array $payload, array &$seen): ?string
    {
        $key = match ($module) {
            'contacts' => filled($payload['email'] ?? null) ? Str::lower((string) $payload['email']) : null,
            'companies' => filled($payload['tax_number'] ?? null) ? 'tax:'.Str::lower((string) $payload['tax_number']) : 'name:'.Str::lower((string) ($payload['name'] ?? '')),
            'deals' => Str::lower((string) ($payload['title'] ?? '')).'|'.($payload['company_id'] ?? '').'|'.($payload['contact_id'] ?? ''),
            default => null,
        };

        if (! $key) {
            return null;
        }

        if (in_array($key, $seen[$module] ?? [], true)) {
            return 'Duplicate row in import file.';
        }

        $seen[$module][] = $key;

        if ($module === 'deals') {
            $exists = Deal::query()
                ->where('title', $payload['title'] ?? '')
                ->when($payload['company_id'] ?? null, fn ($query, int $companyId) => $query->where('company_id', $companyId))
                ->when($payload['contact_id'] ?? null, fn ($query, int $contactId) => $query->where('contact_id', $contactId))
                ->exists();

            return $exists ? 'Duplicate deal already exists.' : null;
        }

        return null;
    }

    /**
     * @return array{row: int, message: string, values: array<string, mixed>}
     */
    private function error(int $row, string $message, array $values): array
    {
        return [
            'row' => $row,
            'message' => $message,
            'values' => $values,
        ];
    }

    /**
     * @param  list<array{row: int, message: string, values: array<string, mixed>}>  $errors
     */
    private function writeErrorReport(CrmImport $import, array $errors): ?string
    {
        if ($errors === []) {
            return null;
        }

        $disk = $import->disk ?: config('crm.data_transfer.disk', 'local');
        $path = 'crm/import-errors/'.$import->public_id.'.csv';
        $tmp = tempnam(sys_get_temp_dir(), 'crm-import-errors-');
        $handle = fopen($tmp, 'w');

        fputcsv($handle, ['row', 'message', 'values']);

        foreach ($errors as $error) {
            fputcsv($handle, [
                $error['row'],
                $error['message'],
                json_encode($error['values'], JSON_UNESCAPED_SLASHES),
            ]);
        }

        fclose($handle);
        Storage::disk($disk)->put($path, file_get_contents($tmp) ?: '');
        @unlink($tmp);

        return $path;
    }

    /**
     * @return Collection<int, mixed>
     */
    private function exportRows(string $module, Request $request): Collection
    {
        return match ($module) {
            'contacts' => Contact::query()
                ->with(['company', 'owner', 'tags'])
                ->search($request->string('search')->toString())
                ->when($request->filled('owner_id'), fn ($query) => $query->where('owner_id', $request->integer('owner_id')))
                ->when($request->filled('tag_id'), fn ($query) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereKey($request->integer('tag_id'))))
                ->orderBy('full_name')
                ->get()
                ->map(fn (Contact $contact): array => [
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
                ]),
            'companies' => Company::query()
                ->with(['owner', 'tags'])
                ->withCount(['contacts', 'deals', 'quotes'])
                ->search($request->string('search')->toString())
                ->when($request->filled('sector'), fn ($query) => $query->where('sector', $request->string('sector')->toString()))
                ->when($request->filled('owner_id'), fn ($query) => $query->where('owner_id', $request->integer('owner_id')))
                ->when($request->filled('tag_id'), fn ($query) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereKey($request->integer('tag_id'))))
                ->orderBy('name')
                ->get()
                ->map(fn (Company $company): array => [
                    $company->name,
                    $company->email,
                    $company->phone,
                    $company->website,
                    $company->tax_number,
                    $company->tax_office,
                    $company->sector,
                    $company->city,
                    $company->country,
                    $company->owner?->name,
                    $company->tags->pluck('name')->implode('|'),
                    $company->contacts_count,
                    $company->deals_count,
                    $company->quotes_count,
                ]),
            'deals' => Deal::query()
                ->with(['company', 'contact', 'stage', 'owner', 'tags'])
                ->when($request->filled('search'), fn ($query) => $query->where('title', 'like', '%'.$request->string('search')->toString().'%'))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
                ->when($request->filled('owner_id'), fn ($query) => $query->where('owner_id', $request->integer('owner_id')))
                ->when($request->filled('tag_id'), fn ($query) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereKey($request->integer('tag_id'))))
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn (Deal $deal): array => [
                    $deal->title,
                    $deal->company?->name,
                    $deal->contact?->full_name,
                    $deal->stage?->name,
                    $deal->status,
                    $deal->value,
                    $deal->currency,
                    $deal->probability,
                    $deal->expected_close_date?->toDateString(),
                    $deal->owner?->name,
                    $deal->tags->pluck('name')->implode('|'),
                    $deal->lost_reason,
                ]),
            'quotes' => Quote::query()
                ->with(['company', 'contact', 'deal', 'owner', 'tags'])
                ->when($request->filled('search'), fn ($query) => $query->where('quote_number', 'like', '%'.$request->string('search')->toString().'%'))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
                ->when($request->filled('owner_id'), fn ($query) => $query->where('owner_id', $request->integer('owner_id')))
                ->when($request->filled('tag_id'), fn ($query) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereKey($request->integer('tag_id'))))
                ->orderByDesc('updated_at')
                ->get()
                ->map(fn (Quote $quote): array => [
                    $quote->quote_number,
                    $quote->company?->name,
                    $quote->contact?->full_name,
                    $quote->deal?->title,
                    $quote->status,
                    $quote->currency,
                    $quote->subtotal,
                    $quote->discount_total,
                    $quote->tax_total,
                    $quote->grand_total,
                    $quote->valid_until?->toDateString(),
                    $quote->owner?->name,
                    $quote->tags->pluck('name')->implode('|'),
                ]),
            default => collect(),
        };
    }

    private function companyByName(?string $name): ?Company
    {
        return filled($name) ? Company::query()->where('name', trim((string) $name))->first() : null;
    }

    private function contactByEmail(?string $email): ?Contact
    {
        return filled($email) ? Contact::query()->where('email', trim((string) $email))->first() : null;
    }

    private function stageByName(?string $name): ?DealStage
    {
        return filled($name) ? DealStage::query()->where('name', trim((string) $name))->first() : null;
    }

    private function userIdByEmail(?string $email): ?int
    {
        return filled($email) ? User::query()->where('email', trim((string) $email))->value('id') : null;
    }

    private function payloadValue(array $payload, string $key, ?string $default = null): ?string
    {
        $value = trim((string) ($payload[$key] ?? ''));

        return $value === '' ? $default : $value;
    }

    private function ensureImportModule(string $module): void
    {
        abort_unless(in_array($module, self::IMPORT_MODULES, true), 404);
    }

    private function ensureExportModule(string $module): void
    {
        abort_unless(in_array($module, self::EXPORT_MODULES, true), 404);
    }
}
