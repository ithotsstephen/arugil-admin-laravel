@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Users</h2>
        <p class="muted">Manage registered users and roles.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
</div>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div class="filters">
    <form method="GET">
        <select name="role">
            <option value="">All roles</option>
            <option value="super_admin">Super Admin</option>
            <option value="moderator">Moderator</option>
            <option value="manager">Manager</option>
            <option value="business_owner">Business Owner</option>
            <option value="user">User</option>
        </select>
        <select name="status">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="blocked">Blocked</option>
        </select>
        <button class="btn" type="submit">Filter</button>
    </form>
</div>

<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Role</th>
        <th>Status</th>
        <th>Joined</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->phone }}</td>
            <td><span class="badge">{{ $user->role }}</span></td>
            <td><span class="badge">{{ $user->status }}</span></td>
            <td>{{ $user->created_at?->format('Y-m-d') }}</td>
            <td class="actions">
                <a href="{{ route('admin.users.edit', $user) }}" class="btn">Edit</a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7">No users found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{ $users->links() }}
@endsection
