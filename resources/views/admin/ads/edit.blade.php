@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Edit Advertisement</h2>
        <p class="muted">Modify ad details.</p>
    </div>
</div>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div class="card">
    <form method="POST" action="{{ route('admin.ads.update', $ad) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <input type="text" name="title" value="{{ old('title', $ad->title) }}" placeholder="Title" required>
            <select name="category_id">
                <option value="">All Categories</option>
                @foreach($categories as $parent)
                    <optgroup label="{{ $parent->name }}">
                        @foreach($parent->children as $child)
                            <option value="{{ $child->id }}" {{ (old('category_id', $ad->category_id) == $child->id) ? 'selected' : '' }}>{{ $child->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @include('components.image-uploader', ['name' => 'image_file', 'urlName' => 'image_url', 'existing' => $ad->image_url])
            <input type="text" name="link" value="{{ old('link', $ad->link) }}" placeholder="Click URL">
            <select name="placement" required>
                <option value="home" {{ old('placement', $ad->placement) == 'home' ? 'selected' : '' }}>Home</option>
                <option value="category" {{ old('placement', $ad->placement) == 'category' ? 'selected' : '' }}>Category</option>
                <option value="detail" {{ old('placement', $ad->placement) == 'detail' ? 'selected' : '' }}>Detail</option>
            </select>
            <input type="date" name="start_date" value="{{ old('start_date', optional($ad->start_date)->format('Y-m-d')) }}">
            <input type="date" name="end_date" value="{{ old('end_date', optional($ad->end_date)->format('Y-m-d')) }}">
            <select name="status" required>
                <option value="active" {{ old('status', $ad->status) == 'active' ? 'selected' : '' }}>Active</option>
                <option value="paused" {{ old('status', $ad->status) == 'paused' ? 'selected' : '' }}>Paused</option>
            </select>
            <div style="display:flex; gap:8px;">
                <button class="btn btn-primary" type="submit">Save</button>
            </div>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.ads.destroy', $ad) }}" onsubmit="return confirm('Delete this ad?');" style="margin-top:12px;">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger" type="submit">Delete</button>
    </form>
</div>

@endsection
