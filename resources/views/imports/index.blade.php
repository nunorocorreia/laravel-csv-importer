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
    <label for="file">Upload CSV:</label>
    <input type="file" name="file" id="file" required>
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


