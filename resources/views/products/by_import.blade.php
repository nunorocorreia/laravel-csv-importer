<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products for Import #{{ $import->id }}</title>
</head>
<body>
<p><a href="{{ route('imports.show', $import) }}">← Back to Import</a></p>

<h1>Products created by Import #{{ $import->id }}</h1>

<form method="get" action="{{ route('imports.products', $import) }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name or external id">
    <button type="submit">Search</button>
</form>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
    <tr>
        <th>ID</th>
        <th>External ID</th>
        <th>Name</th>
        <th>Price</th>
        <th>Stock</th>
        <th>Active</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($products as $product)
        <tr>
            <td>{{ $product->id }}</td>
            <td>{{ $product->external_id }}</td>
            <td>{{ $product->name }}</td>
            <td>{{ $product->price }}</td>
            <td>{{ $product->stock }}</td>
            <td>{{ $product->active ? 'Yes' : 'No' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{ $products->links() }}
</body>
</html>


