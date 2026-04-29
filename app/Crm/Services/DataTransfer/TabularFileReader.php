<?php

namespace App\Crm\Services\DataTransfer;

use DateInterval;
use DateTimeInterface;
use Generator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

class TabularFileReader
{
    /**
     * @return array{headers: list<string>, rows: list<array<string, mixed>>, total_rows: int}
     */
    public function preview(UploadedFile $file, int $limit = 5): array
    {
        $headers = [];
        $rows = [];
        $totalRows = 0;

        foreach ($this->rows($file->getRealPath(), $file->getClientOriginalExtension()) as $index => $row) {
            if ($index === 1) {
                $headers = $this->headers($row);

                continue;
            }

            if ($this->blankRow($row)) {
                continue;
            }

            $totalRows++;

            if (count($rows) < $limit) {
                $rows[] = $this->combine($headers, $row);
            }
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => $totalRows,
        ];
    }

    public function countDataRows(string $path, ?string $extension = null): int
    {
        $count = 0;

        foreach ($this->rows($path, $extension) as $index => $row) {
            if ($index > 1 && ! $this->blankRow($row)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return Generator<int, list<string>>
     */
    public function rows(string $path, ?string $extension = null): Generator
    {
        $reader = $this->reader($extension ?: pathinfo($path, PATHINFO_EXTENSION));
        $reader->open($path);
        $rowNumber = 0;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowNumber++;

                    yield $rowNumber => array_map(fn (mixed $value): string => $this->stringValue($value), $row->toArray());
                }

                break;
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * @param  list<string>  $row
     * @return list<string>
     */
    public function headers(array $row): array
    {
        return collect($row)
            ->map(fn (?string $header): string => Str::of((string) $header)->trim()->lower()->snake()->toString())
            ->all();
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string>  $row
     * @return array<string, string>
     */
    public function combine(array $headers, array $row): array
    {
        $payload = [];

        foreach ($headers as $index => $header) {
            if ($header !== '') {
                $payload[$header] = $row[$index] ?? '';
            }
        }

        return $payload;
    }

    /**
     * @param  list<string>  $row
     */
    public function blankRow(array $row): bool
    {
        return collect($row)->every(fn (string $value): bool => trim($value) === '');
    }

    private function reader(string $extension): ReaderInterface
    {
        return match (Str::lower($extension)) {
            'xlsx' => new XlsxReader,
            default => new CsvReader,
        };
    }

    private function stringValue(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof DateInterval) {
            return $value->format('%r%a days');
        }

        return trim((string) $value);
    }
}
