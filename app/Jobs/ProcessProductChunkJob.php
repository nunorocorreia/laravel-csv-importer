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

class ProcessProductChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $importId,
        public int $batchNumber,
        public string $path
    ) {
    }

    public function handle(): void
    {
        $import = Import::find($this->importId);

        if (!$import) {
            return;
        }

        if (!Storage::exists($this->path)) {
            $import->update([
                'status' => 'failed',
                'error_message' => "Missing batch file: {$this->path}",
            ]);

            return;
        }

        $payload = json_decode(Storage::get($this->path), true);

        if (!is_array($payload)) {
            Storage::delete($this->path);
            $import->update([
                'status' => 'failed',
                'error_message' => "Corrupted batch file: {$this->path}",
            ]);

            return;
        }

        $validRows = [];
        $errorCount = 0;

        foreach ($payload as $rowPayload) {
            $rowNumber = $rowPayload['row_number'] ?? null;
            $row = $rowPayload['data'] ?? [];

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

            $validRows[] = $this->transformRowToProductData($row, $import->id);
        }

        $processedCount = 0;

        if (count($validRows) > 0) {
            $this->storeBatch($validRows);
            $processedCount = count($validRows);
        }

        if ($processedCount > 0) {
            $import->increment('processed_rows', $processedCount);
        }

        if ($errorCount > 0) {
            $import->increment('error_count', $errorCount);
        }

        $import->increment('completed_batches');

        Storage::delete($this->path);

        $import->refresh();

        if ($import->batch_count !== null && $import->completed_batches >= $import->batch_count) {
            $import->update([
                'status' => 'finished',
                'finished_at' => now(),
            ]);
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
            'price' => isset($row['price']) ? (float)$row['price'] : null,
            'stock' => isset($row['stock']) ? (int)$row['stock'] : 0,
            'active' => isset($row['active']) ? (bool)$row['active'] : true,
            'created_at' => now(),
            'updated_at' => now(),
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

    public function failed(Exception $exception): void
    {
        $import = Import::find($this->importId);

        if (!$import) {
            return;
        }

        $import->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}


