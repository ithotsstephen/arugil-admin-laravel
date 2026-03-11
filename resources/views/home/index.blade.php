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
        <form id="search-form" method="GET" action="/">
            <input 
                id="search-input"
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
        <div id="results-grid" class="grid">
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

        <script>
            (function(){
                const form = document.getElementById('search-form');
                const input = document.getElementById('search-input');
                const resultsGrid = document.getElementById('results-grid');

                function renderCard(b) {
                    const a = document.createElement('a');
                    a.href = '/business/' + b.id;
                    a.className = 'card';

                    const imgWrap = document.createElement('div');
                    imgWrap.className = 'card-image';
                    if (b.image_url) {
                        const img = document.createElement('img');
                        img.src = b.image_url;
                        img.alt = b.name;
                        imgWrap.appendChild(img);
                    } else {
                        imgWrap.textContent = (b.name || '').charAt(0).toUpperCase();
                    }

                    const body = document.createElement('div');
                    body.className = 'card-body';
                    if (b.is_featured) {
                        const span = document.createElement('span');
                        span.className = 'badge badge-featured';
                        span.textContent = '⭐ Featured';
                        body.appendChild(span);
                    }
                    const h3 = document.createElement('h3');
                    h3.className = 'card-title';
                    h3.textContent = b.name;
                    const cat = document.createElement('span');
                    cat.className = 'card-category';
                    cat.textContent = (b.category && b.category.name) || '';
                    const p = document.createElement('p');
                    p.className = 'card-description';
                    p.textContent = b.description || '';

                    body.appendChild(h3);
                    body.appendChild(cat);
                    body.appendChild(p);

                    a.appendChild(imgWrap);
                    a.appendChild(body);

                    return a;
                }

                async function doSearch(q) {
                    const url = new URL('/api/v1/businesses', window.location.origin);
                    url.searchParams.set('q', q);
                    url.searchParams.set('per_page', '12');

                    try {
                        const res = await fetch(url.toString(), { credentials: 'same-origin' });
                        if (!res.ok) throw new Error('Network error');
                        const data = await res.json();

                        // clear grid
                        resultsGrid.innerHTML = '';

                        if (data.data && data.data.length) {
                            data.data.forEach(b => {
                                resultsGrid.appendChild(renderCard(b));
                            });
                        } else {
                            resultsGrid.innerHTML = '<p>No businesses found</p>';
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }

                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    const q = input.value.trim();
                    doSearch(q);
                });
            })();
        </script>
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
