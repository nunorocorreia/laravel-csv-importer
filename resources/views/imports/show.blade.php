<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Import #{{ $import->id }}</title>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusEl = document.getElementById('status');
            const progressEl = document.getElementById('progress');
            const batchEl = document.getElementById('batch-progress');

            const poll = () => {
                fetch('{{ route('imports.status', $import) }}')
                    .then(response => response.json())
                    .then(data => {
                        statusEl.textContent = data.status;
                        if (data.total_rows) {
                            const percent = Math.round((data.processed_rows / data.total_rows) * 100);
                            progressEl.textContent = `${data.processed_rows} / ${data.total_rows} (${percent}%)`;
                        } else {
                            progressEl.textContent = `${data.processed_rows} processed`;
                        }

                        if (data.batch_count !== null) {
                            batchEl.textContent = `${data.completed_batches} / ${data.batch_count}`;
                        } else {
                            batchEl.textContent = '—';
                        }

                        if (data.status === 'finished' || data.status === 'failed') {
                            return;
                        }

                        setTimeout(poll, 3000);
                    })
                    .catch(() => setTimeout(poll, 5000));
            };

            poll();
        });
    </script>
</head>
<body>
<p><a href="{{ route('imports.index') }}">← Back</a></p>

<h1>Import #{{ $import->id }}</h1>

<p>Status: <strong id="status">{{ $import->status }}</strong></p>
<p>Progress: <span id="progress">{{ $import->processed_rows }} / {{ $import->total_rows ?? 'N/A' }}</span></p>
<p>Errors: {{ $import->error_count }}</p>
<p>Batches: <span id="batch-progress">{{ ! is_null($import->batch_count) ? $import->completed_batches . ' / ' . $import->batch_count : '—' }}</span></p>
<p>Started: {{ optional($import->started_at)->toDayDateTimeString() ?? 'Pending' }}</p>
<p>Finished: {{ optional($import->finished_at)->toDayDateTimeString() ?? 'N/A' }}</p>

@if ($import->error_message)
    <p>Last error: {{ $import->error_message }}</p>
@endif

<p><a href="{{ route('imports.products', $import) }}">View Products from this Import</a></p>

<h2>Row Errors</h2>
@if ($import->errors->isEmpty())
    <p>No row-level errors.</p>
@else
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
        <tr>
            <th>Row</th>
            <th>Error</th>
            <th>Data</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($import->errors as $error)
            <tr>
                <td>{{ $error->row_number }}</td>
                <td>{{ $error->error_message }}</td>
                <td><pre>{{ json_encode($error->row_data, JSON_PRETTY_PRINT) }}</pre></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
</body>
</html>


