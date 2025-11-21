<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\ImportError;
use App\Models\Product;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Import $import;

    public int $chunkSize = 1000;

    public function __construct(Import $import)
    {
        $this->import = $import;
    }

    public function handle(): void
    {
        $import = $this->import->fresh();

        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $path = $import->file_path;

        if (! Storage::exists($path)) {
            $import->update([
                'status' => 'failed',
                'error_message' => 'File not found',
            ]);

            return;
        }

        $fullPath = Storage::path($path);
        $handle = fopen($fullPath, 'r');

        if (! $handle) {
            $import->update([
                'status' => 'failed',
                'error_message' => 'Could not open file',
            ]);

            return;
        }

        $rowNumber = 0;
        $header = null;
        $batch = [];
        $errorCount = 0;

        try {
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                $rowNumber++;

                if ($rowNumber === 1) {
                    $header = $data;
                    continue;
                }

                if ($header !== null && count($data) !== count($header)) {
                    $errorCount++;
                    ImportError::create([
                        'import_id' => $import->id,
                        'row_number' => $rowNumber,
                        'row_data' => ['raw' => $data],
                        'error_message' => 'Column count mismatch',
                    ]);
                    continue;
                }

                $row = $header
                    ? array_combine($header, $data)
                    : $this->mapRowWithoutHeader($data);

                $validationError = $this->validateRow($row);

                if ($validationError) {
                    $errorCount++;
                    ImportError::create([
                        'import_id' => $import->id,
                        'row_number' => $rowNumber,
                        'row_data' => $row,
                        'error_message' => $validationError,
                    ]);
                    continue;
                }

                $batch[] = $this->transformRowToProductData($row, $import->id);

                if (count($batch) >= $this->chunkSize) {
                    $this->storeBatch($batch);
                    $import->increment('processed_rows', count($batch));
                    $batch = [];
                }
            }

            if (count($batch) > 0) {
                $this->storeBatch($batch);
                $import->increment('processed_rows', count($batch));
            }

            if ($import->total_rows === null) {
                $import->total_rows = $import->processed_rows + $errorCount;
            }

            $import->error_count = $errorCount;
            $import->status = 'finished';
            $import->finished_at = now();
            $import->save();
        } catch (Exception $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            report($e);
        } finally {
            fclose($handle);
        }
    }

    protected function validateRow(array $row): ?string
    {
        if (empty($row['external_id'] ?? null)) {
            return 'Missing external_id';
        }

        if (empty($row['name'] ?? null)) {
            return 'Missing name';
        }

        return null;
    }

    protected function transformRowToProductData(array $row, int $importId): array
    {
        return [
            'import_id' => $importId,
            'external_id' => $row['external_id'],
            'name' => $row['name'],
            'price' => isset($row['price']) ? (float) $row['price'] : null,
            'stock' => isset($row['stock']) ? (int) $row['stock'] : 0,
            'active' => isset($row['active']) ? (bool) $row['active'] : true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function mapRowWithoutHeader(array $data): array
    {
        return [
            'external_id' => $data[0] ?? null,
            'name' => $data[1] ?? null,
            'price' => $data[2] ?? null,
            'stock' => $data[3] ?? null,
            'active' => $data[4] ?? null,
        ];
    }

    protected function storeBatch(array $batch): void
    {
        DB::transaction(function () use ($batch) {
            Product::upsert(
                $batch,
                ['external_id'],
                ['name', 'price', 'stock', 'active', 'import_id', 'updated_at']
            );
        });
    }
}


