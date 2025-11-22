<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Jobs\ProcessImportJob;
use App\Http\Requests\StoreImportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View|JsonResponse
    {
        $imports = Import::recent()->paginate(20);

        if (request()->wantsJson()) {
            return response()->json($imports);
        }

        return view('imports.index', compact('imports'));
    }

    public function show(Import $import): View|JsonResponse
    {
        $import->load('errors');

        if (request()->wantsJson()) {
            return response()->json($import);
        }

        return view('imports.show', compact('import'));
    }

    public function store(StoreImportRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $disk = config('imports.storage_disk', 's3');
        $filename = $data['originalFilename'] ?? null;
        $fileKey = $data['fileKey'] ?? null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $fileKey = $file->store('imports', $disk);
        }

        $import = Import::create([
            'filename' => $filename,
            'file_path' => $fileKey,
            'status' => 'pending',
            'total_rows' => null,
            'processed_rows' => 0,
            'error_count' => 0,
        ]);

        ProcessImportJob::dispatch($import);

        if ($request->wantsJson()) {
            return response()->json([
                'importId' => $import->id,
                'status' => $import->status,
            ], 201);
        }

        return redirect()
            ->route('imports.show', $import)
            ->with('status', 'Import started!');
    }

    public function status(Import $import): JsonResponse
    {
        return response()->json([
            'id' => $import->id,
            'status' => $import->status,
            'processed_rows' => $import->processed_rows,
            'total_rows' => $import->total_rows,
            'error_count' => $import->error_count,
            'batch_count' => $import->batch_count,
            'completed_batches' => $import->completed_batches,
            'started_at' => optional($import->started_at)->toDateTimeString(),
            'finished_at' => optional($import->finished_at)->toDateTimeString(),
        ]);
    }
}


