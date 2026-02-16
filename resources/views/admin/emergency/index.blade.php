@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Emergency Numbers</h2>
        <p class="muted">Manage static emergency numbers.</p>
    </div>
</div>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div class="card" style="margin-bottom: 16px;">
    <form method="POST" action="{{ route('admin.emergency.store') }}">
        @csrf
        <div class="filters">
            <input type="text" name="category" placeholder="Category" required>
            <input type="text" name="name" placeholder="Name" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <button class="btn btn-primary" type="submit">Add</button>
        </div>
    </form>
</div>

<table>
    <thead>
    <tr>
        <th>Category</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($numbers as $number)
        <tr>
            <td>{{ $number->category }}</td>
            <td>{{ $number->name }}</td>
            <td>{{ $number->phone }}</td>
            <td>
                <form method="POST" action="{{ route('admin.emergency.destroy', $number) }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="4">No emergency numbers yet.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{ $numbers->links() }}
@endsection
