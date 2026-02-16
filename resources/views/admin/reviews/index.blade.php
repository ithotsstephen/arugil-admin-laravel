@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Reviews</h2>
        <p class="muted">Moderate user reviews.</p>
    </div>
</div>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<table>
    <thead>
    <tr>
        <th>Business</th>
        <th>User</th>
        <th>Rating</th>
        <th>Status</th>
        <th>Comment</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($reviews as $review)
        <tr>
            <td>{{ $review->business?->name }}</td>
            <td>{{ $review->user?->name }}</td>
            <td>{{ $review->rating }}</td>
            <td><span class="badge">{{ $review->status }}</span></td>
            <td>{{ $review->comment }}</td>
            <td class="actions">
                <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.reviews.reject', $review) }}">
                    @csrf
                    <button class="btn" type="submit">Reject</button>
                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6">No reviews found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{ $reviews->links() }}
@endsection
