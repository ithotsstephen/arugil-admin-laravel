@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Businesses</h2>
        <p class="muted">Approve or reject listings.</p>
    </div>
    <a href="{{ route('admin.businesses.create') }}" class="btn btn-primary">Add Business</a>
</div>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div class="filters">
    <form method="GET" style="display: flex; gap: 12px; align-items: end;">
        <div>
            <label style="display: block; margin-bottom: 6px; font-size: 13px; color: var(--muted);">Search by WhatsApp/Phone</label>
            <input type="text" name="search" placeholder="Enter WhatsApp or phone number" value="{{ request('search') }}" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); min-width: 250px;">
        </div>
        <div>
            <label style="display: block; margin-bottom: 6px; font-size: 13px; color: var(--muted);">Status</label>
            <select name="status" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border);">
                <option value="">All</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
        </div>
        <button class="btn" type="submit">Search</button>
        @if(request('search') || request('status'))
            <a href="{{ route('admin.businesses.index') }}" class="btn" style="background: #6b7280;">Clear</a>
        @endif
    </form>
</div>

<table>
    <thead>
    <tr>
        <th>Business</th>
        <th>Category</th>
        <th>Owner</th>
        <th>Status</th>
        <th>Featured</th>
        <th>Expiry</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($businesses as $business)
        <tr>
            <td>{{ $business->name }}</td>
            <td>{{ $business->category?->name }}</td>
            <td>{{ $business->owner?->name }}</td>
            <td>
                <span class="badge">{{ $business->is_approved ? 'Approved' : 'Pending' }}</span>
            </td>
            <td>
                <span class="badge">{{ $business->is_featured ? 'Yes' : 'No' }}</span>
            </td>
            <td>
                @if($business->expiry_date)
                    {{ $business->expiry_date->format('Y-m-d') }}
                    @if($business->isExpired())
                        <span style="color: #f87171;">⚠️</span>
                    @endif
                @else
                    —
                @endif
            </td>
            <td class="actions">
                <a href="{{ route('admin.businesses.edit', $business) }}" class="btn">Edit</a>
                @if(auth()->user()->hasRole('super_admin', 'moderator'))
                    <form method="POST" action="{{ route('admin.businesses.approve', $business) }}" style="display: inline;">
                        @csrf
                        <button class="btn btn-primary" type="submit">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.businesses.reject', $business) }}" style="display: inline;">
                        @csrf
                        <button class="btn" type="submit">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('admin.businesses.feature', $business) }}" style="display: inline;">
                        @csrf
                        <button class="btn" type="submit">Toggle Featured</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7">No businesses found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{ $businesses->links() }}
@endsection
