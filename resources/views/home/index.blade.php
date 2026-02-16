@extends('layouts.public')

@section('title', 'Business Directory - Find Local Businesses')

@section('content')
<div class="hero">
    <div class="container">
        <h2>Find the Best Local Businesses</h2>
        <p>Discover and connect with trusted businesses in your area</p>
    </div>
</div>

<div class="container">
    <div class="filters">
        <form method="GET" action="/">
            <input 
                type="text" 
                name="search" 
                placeholder="Search businesses..." 
                value="{{ request('search') }}"
            >
            <select name="category">
                <option value="">All Categories</option>
                @foreach($categories as $parent)
                    <optgroup label="{{ $parent->name }}">
                        @foreach($parent->children as $child)
                            <option 
                                value="{{ $child->id }}" 
                                {{ request('category') == $child->id ? 'selected' : '' }}
                            >
                                {{ $child->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <button type="submit">Search</button>
        </form>
    </div>

    @if($businesses->count() > 0)
        <div class="grid">
            @foreach($businesses as $business)
                <a href="/business/{{ $business->id }}" class="card">
                    <div class="card-image">
                        @if($business->image_url)
                            <img src="{{ $business->image_url }}" alt="{{ $business->name }}">
                        @else
                            {{ strtoupper(substr($business->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="card-body">
                        @if($business->is_featured)
                            <span class="badge badge-featured">⭐ Featured</span>
                        @endif
                        <h3 class="card-title">{{ $business->name }}</h3>
                        <span class="card-category">{{ $business->category->name }}</span>
                        <p class="card-description">{{ $business->description }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="pagination">
            {{ $businesses->links() }}
        </div>
    @else
        <div class="empty-state">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <h3>No businesses found</h3>
            <p>Try adjusting your search or filters</p>
        </div>
    @endif
</div>
@endsection
