@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Edit User</h2>
        <p class="muted">Update user information</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.users.update', $user) }}">
    @csrf
    @method('PUT')
    <div class="card" style="max-width: 600px;">
        <label>Name*</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
        
        <label>Email*</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
        
        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}">
        
        <label>Password</label>
        <input type="password" name="password">
        <p style="font-size: 12px; color: var(--muted); margin-top: -10px; margin-bottom: 14px;">Leave blank to keep current password</p>
        
        <label>Role*</label>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="super_admin" {{ old('role', $user->role) == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
            <option value="moderator" {{ old('role', $user->role) == 'moderator' ? 'selected' : '' }}>Moderator</option>
            <option value="manager" {{ old('role', $user->role) == 'manager' ? 'selected' : '' }}>Manager (Business Only)</option>
            <option value="business_owner" {{ old('role', $user->role) == 'business_owner' ? 'selected' : '' }}>Business Owner</option>
            <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>User</option>
        </select>
        <p style="font-size: 12px; color: var(--muted); margin-top: -10px; margin-bottom: 14px;">Manager role can only add/edit businesses</p>
        
        <label>Status*</label>
        <select name="status" required>
            <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
            <option value="blocked" {{ old('status', $user->status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
        </select>
        
        <div style="display: flex; gap: 8px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Update User</button>
            <a href="{{ route('admin.users.index') }}" class="btn">Cancel</a>
        </div>
    </div>
</form>

<style>
    label { display: block; margin-bottom: 6px; font-size: 13px; color: var(--muted); font-weight: 500; }
    input, select { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 14px; }
</style>
@endsection
