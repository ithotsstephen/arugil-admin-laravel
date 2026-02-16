@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Jobs</h2>
        <p class="muted">Job listings and status.</p>
    </div>
    <a href="{{ route('admin.jobs.create') }}" class="btn btn-primary">Add Job</a>
</div>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<table>
    <thead>
    <tr>
        <th>Title</th>
        <th>Business</th>
        <th>Status</th>
        <th>Expiry</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($jobs as $job)
        <tr>
            <td>{{ $job->title }}</td>
            <td>{{ $job->business?->name }}</td>
            <td><span class="badge">{{ $job->status }}</span></td>
            <td>{{ $job->expiry_date?->format('Y-m-d') }}</td>
            <td class="actions">
                <a href="{{ route('admin.jobs.edit', $job) }}" class="btn">Edit</a>
                <form method="POST" action="{{ route('admin.jobs.approve', $job) }}" style="display: inline;">
                    @csrf
                    <button class="btn btn-primary" type="submit">Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.jobs.reject', $job) }}" style="display: inline;">
                    @csrf
                    <button class="btn" type="submit">Reject</button>
                </form>
                <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit" onclick="return confirm('Delete this job?')">Delete</button>
                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5">No jobs yet.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{ $jobs->links() }}
@endsection
