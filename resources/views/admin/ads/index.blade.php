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
    <form method="GET" action="{{ route('admin.ads.index') }}">
        <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
            <select name="status" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="paused" {{ request('status') == 'paused' ? 'selected' : '' }}>Paused</option>
            </select>
            <select name="main_category" onchange="this.form.submit()">
                <option value="">All Main Categories</option>
                @foreach($categories as $parent)
                    <option value="{{ $parent->id }}" {{ request('main_category') == $parent->id ? 'selected' : '' }}>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
            <select name="sub_category" onchange="this.form.submit()">
                <option value="">All Sub Categories</option>
                @foreach($subCategories as $subcategory)
                    <option value="{{ $subcategory->id }}" {{ request('sub_category') == $subcategory->id ? 'selected' : '' }}>
                        {{ $subcategory->name }}
                    </option>
                @endforeach
            </select>
            <select name="sort" onchange="this.form.submit()">
                <option value="">Sort by latest</option>
                <option value="main_category_asc" {{ request('sort') == 'main_category_asc' ? 'selected' : '' }}>Main Category (A-Z)</option>
                <option value="main_category_desc" {{ request('sort') == 'main_category_desc' ? 'selected' : '' }}>Main Category (Z-A)</option>
                <option value="sub_category_asc" {{ request('sort') == 'sub_category_asc' ? 'selected' : '' }}>Sub Category (A-Z)</option>
                <option value="sub_category_desc" {{ request('sort') == 'sub_category_desc' ? 'selected' : '' }}>Sub Category (Z-A)</option>
            </select>
        </div>
    </form>
</div>

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
        <th>Main Category</th>
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
            <td>{{ $ad->category?->parent?->name ?? $ad->category?->name ?? '—' }}</td>
            <td>{{ $ad->category?->parent ? $ad->category?->name : '—' }}</td>
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
            <td colspan="8">No ads found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{ $ads->links() }}
@endsection
