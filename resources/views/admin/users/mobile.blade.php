@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Mobile Users</h2>
        <p class="muted">Users who registered with a phone number.</p>
    </div>
</div>

<div class="filters">
    <form method="GET">
        <input type="text" name="search" placeholder="Search name, email, phone" value="{{ request('search') }}">
        <button class="btn" type="submit">Search</button>
    </form>
</div>

<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Status</th>
        <th>Joined</th>
    </tr>
    </thead>
    <tbody>
    @forelse($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->phone ?? '—' }}</td>
            <td>{{ $user->email }}</td>
            <td><span class="badge">{{ $user->status }}</span></td>
            <td>{{ $user->created_at?->format('Y-m-d') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="5">No mobile users found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{ $users->links() }}
@endsection
