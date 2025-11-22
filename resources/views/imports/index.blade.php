<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Imports</title>
</head>
<body>
<h1>Recent Imports</h1>

@if (session('status'))
    <p>{{ session('status') }}</p>
@endif

<form action="{{ route('imports.store') }}" method="post" enctype="multipart/form-data">
    @csrf
    <fieldset>
        <legend>Upload CSV (optional)</legend>
        <p>You can still upload a local CSV and we will push it to the configured cloud disk.</p>
        <label for="file">Choose CSV:</label>
        <input type="file" name="file" id="file">
    </fieldset>

    <p><strong>OR</strong> provide the cloud object details if the file is already uploaded:</p>
    <div>
        <label for="fileKey">Cloud file key</label>
        <input type="text" name="fileKey" id="fileKey" placeholder="imports/my-upload.csv" value="{{ old('fileKey') }}">
    </div>
    <div>
        <label for="originalFilename">Original filename</label>
        <input type="text" name="originalFilename" id="originalFilename" value="{{ old('originalFilename') }}">
    </div>

    <p><small>When a cloud file key is provided, the upload field is ignored.</small></p>

    <button type="submit">Start Import</button>
</form>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
    <tr>
        <th>ID</th>
        <th>Filename</th>
        <th>Status</th>
        <th>Processed / Total</th>
        <th>Errors</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($imports as $import)
        <tr>
            <td>{{ $import->id }}</td>
            <td>{{ $import->filename }}</td>
            <td>{{ $import->status }}</td>
            <td>{{ $import->processed_rows }} / {{ $import->total_rows ?? 'N/A' }}</td>
            <td>{{ $import->error_count }}</td>
            <td><a href="{{ route('imports.show', $import) }}">View</a></td>
        </tr>
    @endforeach
    </tbody>
</table>

{{ $imports->links() }}
</body>
</html>


