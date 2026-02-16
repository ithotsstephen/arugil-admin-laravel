@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Advertisements</h2>
        <p class="muted">Manage banner ads and placements.</p>
    </div>
</div>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div class="card" style="margin-bottom: 16px;">
    <form method="POST" action="{{ route('admin.ads.store') }}">
        @csrf
        <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <input type="text" name="title" placeholder="Title" required>
            <select name="category_id">
                <option value="">All Categories</option>
                @foreach($categories as $parent)
                    <optgroup label="{{ $parent->name }}">
                        @foreach($parent->children as $child)
                            <option value="{{ $child->id }}">{{ $child->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <input type="text" name="image_url" placeholder="Image URL" required>
            <input type="text" name="link" placeholder="Click URL">
            <select name="placement" required>
                <option value="home">Home</option>
                <option value="category">Category</option>
                <option value="detail">Detail</option>
            </select>
            <input type="date" name="start_date">
            <input type="date" name="end_date">
            <select name="status" required>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
            </select>
            <button class="btn btn-primary" type="submit">Create</button>
        </div>
    </form>
</div>

<table>
    <thead>
    <tr>
        <th>Title</th>
        <th>Category</th>
        <th>Placement</th>
        <th>Status</th>
        <th>Clicks</th>
        <th>Duration</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    @forelse($ads as $ad)
        <tr>
            <td>{{ $ad->title }}</td>
            <td>{{ $ad->category?->name ?? '—' }}</td>
            <td>{{ $ad->placement }}</td>
            <td><span class="badge">{{ $ad->status }}</span></td>
            <td>{{ $ad->clicks }}</td>
            <td>{{ $ad->start_date?->format('Y-m-d') }} - {{ $ad->end_date?->format('Y-m-d') }}</td>
            <td>
                <form method="POST" action="{{ route('admin.ads.toggle', $ad) }}">
                    @csrf
                    <button class="btn" type="submit">Toggle</button>
                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7">No ads found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{ $ads->links() }}
@endsection
