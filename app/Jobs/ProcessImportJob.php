<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\ImportError;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Import $import;

    public int $batchSize = 1000;

    public string $delimiter = ';';

    public function __construct(Import $import)
    {
        $this->import = $import;
    }

    public function handle(): void
    {
        $import = $this->import->fresh();

        if (!$import) {
            return;
        }

        $import->update([
            'status' => 'processing',
            'started_at' => now(),
            'processed_rows' => 0,
            'error_count' => 0,
            'completed_batches' => 0,
        ]);

        $path = $import->file_path;

        if (!Storage::exists($path)) {
            $import->update([
                'status' => 'failed',
                'error_message' => 'File not found',
            ]);

            return;
        }

        $fullPath = Storage::path($path);
        $handle = fopen($fullPath, 'r');

        if (!$handle) {
            $import->update([
                'status' => 'failed',
                'error_message' => 'Could not open file',
            ]);

            return;
        }

        $header = fgetcsv($handle, 0, $this->delimiter);

        if ($header === false) {
            fclose($handle);

            $import->update([
                'status' => 'failed',
                'error_message' => 'Missing CSV header row',
            ]);

            return;
        }

        $batchPayload = [];
        $batchNumber = 0;
        $totalRows = 0;
        $rowNumber = 1; // header row
        $prepareStageErrors = 0;

        Storage::makeDirectory('import_batches');

        try {
            while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
                $rowNumber++;
                $totalRows++;

                if (count($row) !== count($header)) {
                    $prepareStageErrors++;
                    ImportError::create([
                        'import_id' => $import->id,
                        'row_number' => $rowNumber,
                        'row_data' => ['raw' => $row],
                        'error_message' => 'Column count mismatch',
                    ]);
                    continue;
                }

                $mappedRow = array_combine($header, $row);

                $batchPayload[] = [
                    'row_number' => $rowNumber,
                    'data' => $mappedRow,
                ];

                if (count($batchPayload) >= $this->batchSize) {
                    $this->queueBatch($import->id, ++$batchNumber, $batchPayload);
                    $batchPayload = [];
                }
            }

            if (count($batchPayload) > 0) {
                $this->queueBatch($import->id, ++$batchNumber, $batchPayload);
            }
        } catch (Exception $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            report($e);
        } finally {
            fclose($handle);
        }

        if ($batchNumber === 0) {
            $import->update([
                'total_rows' => $totalRows,
                'batch_count' => 0,
                'completed_batches' => 0,
                'error_count' => $prepareStageErrors,
                'status' => 'finished',
                'finished_at' => now(),
            ]);

            return;
        }

        $import->update([
            'total_rows' => $totalRows,
            'batch_count' => $batchNumber,
            'completed_batches' => 0,
            'error_count' => $prepareStageErrors,
        ]);
    }

    protected function queueBatch(int $importId, int $batchNumber, array $payload): void
    {
        $path = "import_batches/import_{$importId}_batch_{$batchNumber}.json";

        Storage::put($path, json_encode($payload));

        ProcessProductChunkJob::dispatch($importId, $batchNumber, $path);
    }
}


