@extends('layouts.public')

@section('title', $business->name . ' - Business Directory')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .detail-hero {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        padding: 40px 0;
    }

    .detail-container {
        max-width: 1200px;
        margin: -50px auto 0;
        padding: 0 20px;
    }

    .detail-card {
        background: var(--card);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .detail-header {
        padding: 32px;
        border-bottom: 1px solid var(--border);
    }

    .detail-image {
        width: 100%;
        height: 400px;
        object-fit: cover;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 120px;
    }

    .detail-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .detail-title {
        font-size: 32px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .detail-meta {
        color: var(--text-muted);
        font-size: 14px;
    }

    .detail-body {
        padding: 32px;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 32px;
    }

    .section {
        margin-bottom: 32px;
    }

    .section h3 {
        font-size: 20px;
        margin-bottom: 16px;
        color: var(--text);
    }

    .section p {
        color: var(--text-muted);
        line-height: 1.8;
    }

    .services-tabs {
        margin-top: 16px;
    }

    .tabs-header {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid var(--border);
        margin-bottom: 20px;
        overflow-x: auto;
        flex-wrap: wrap;
    }

    .tab-button {
        background: transparent;
        border: none;
        padding: 12px 20px;
        font-size: 15px;
        font-weight: 500;
        color: var(--text-muted);
        cursor: pointer;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .tab-button:hover {
        color: var(--primary);
        background: rgba(59, 130, 246, 0.05);
    }

    .tab-button.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        font-weight: 600;
    }

    .tabs-content {
        background: var(--bg);
        padding: 24px;
        border-radius: 8px;
    }

    .tab-pane {
        display: none;
    }

    .tab-pane.active {
        display: block;
    }

    .tab-pane h4 {
        font-size: 20px;
        margin-bottom: 12px;
        color: var(--text);
    }

    .tab-pane p {
        font-size: 15px;
        color: var(--text-muted);
        line-height: 1.8;
        margin: 0;
    }

    .contact-info {
        background: var(--bg);
        padding: 24px;
        border-radius: 8px;
    }

    .contact-item {
        display: flex;
        align-items: start;
        gap: 12px;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid var(--border);
    }

    .contact-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .contact-item svg {
        width: 20px;
        height: 20px;
        color: var(--primary);
        flex-shrink: 0;
        margin-top: 2px;
    }

    .contact-item div {
        flex: 1;
    }

    .contact-item strong {
        display: block;
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 4px;
    }

    .contact-item a {
        color: var(--primary);
        text-decoration: none;
    }

    .contact-item a:hover {
        text-decoration: underline;
    }

    .social-links {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: var(--bg);
        color: var(--text);
        text-decoration: none;
        transition: all 0.2s;
    }

    .social-links a:hover {
        background: var(--primary);
        color: white;
    }

    .gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 16px;
    }

    .gallery img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .gallery img:hover {
        transform: scale(1.05);
    }

    .gallery-controls {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
        align-items: center;
    }

    .view-toggle {
        display: flex;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
    }

    .view-toggle button {
        padding: 8px 16px;
        background: transparent;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        transition: all 0.2s;
    }

    .view-toggle button.active {
        background: var(--primary);
        color: white;
    }

    .carousel-container {
        position: relative;
        width: 100%;
        height: 400px;
        overflow: hidden;
        border-radius: 12px;
        background: #000;
    }

    .carousel-slide {
        display: none;
        width: 100%;
        height: 100%;
    }

    .carousel-slide.active {
        display: block;
    }

    .carousel-slide img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .carousel-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        padding: 12px 16px;
        cursor: pointer;
        font-size: 24px;
        border-radius: 8px;
        transition: background 0.2s;
    }

    .carousel-nav:hover {
        background: rgba(0, 0, 0, 0.8);
    }

    .carousel-prev {
        left: 16px;
    }

    .carousel-next {
        right: 16px;
    }

    .carousel-dots {
        position: absolute;
        bottom: 16px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 8px;
    }

    .carousel-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        border: none;
        cursor: pointer;
        transition: background 0.2s;
    }

    .carousel-dot.active {
        background: white;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }

    .gallery-grid img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .gallery-grid img:hover {
        transform: scale(1.05);
    }

    .related {
        padding: 60px 0;
    }

    .related h3 {
        font-size: 24px;
        margin-bottom: 32px;
        text-align: center;
    }

    #map {
        width: 100%;
        height: 300px;
        border-radius: 8px;
        margin-top: 16px;
    }

    @media (max-width: 768px) {
        .detail-body {
            grid-template-columns: 1fr;
        }

        .detail-title {
            font-size: 24px;
        }
    }
</style>

<div class="detail-hero">
    <div class="container">
        <a href="/" style="color: white; text-decoration: none; display: inline-block; margin-bottom: 16px;">← Back to Directory</a>
    </div>
</div>

<div class="detail-container">
    <div class="detail-card">
        @if($business->image_url)
            <div class="detail-image">
                <img src="{{ $business->image_url }}" alt="{{ $business->name }}">
            </div>
        @endif

        <div class="detail-header">
            <div class="detail-title">
                {{ $business->name }}
                @if($business->is_featured)
                    <span class="badge badge-featured">⭐ Featured</span>
                @endif
            </div>
            <div class="detail-meta">
                <span class="card-category">{{ $business->category->name }}</span>
                <span style="margin: 0 8px;">•</span>
                <span>👁 {{ $business->views }} views</span>
                @if($business->years_of_business)
                    <span style="margin: 0 8px;">•</span>
                    <span>📅 {{ $business->years_of_business }} {{ Str::plural('year', $business->years_of_business) }} in business</span>
                @endif
            </div>
        </div>

        <div class="detail-body">
            <div>
                @if($business->owner_name || $business->owner_image_url)
                    <div class="section" style="padding: 16px; background: var(--bg); border-radius: 8px; margin-bottom: 24px; display: flex; gap: 12px; align-items: center;">
                        @if($business->owner_image_url)
                            <img src="{{ $business->owner_image_url }}" alt="{{ $business->owner_name ?? 'Owner' }}" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover;">
                        @endif
                        <p style="margin: 0; color: var(--text);"><strong>Owner:</strong> {{ $business->owner_name ?? '—' }}</p>
                    </div>
                @endif
                
                @if($business->about_title || $business->description)
                    <div class="section">
                        <h3>{{ $business->about_title ?? 'About' }}</h3>
                        <p>{{ $business->description }}</p>
                    </div>
                @endif

                @if($business->services && count($business->services) > 0)
                    <div class="section">
                        <h3>Services Offered</h3>
                        <div class="services-tabs">
                            <div class="tabs-header">
                                @foreach($business->services as $index => $service)
                                    <button class="tab-button {{ $index === 0 ? 'active' : '' }}" onclick="switchTab({{ $index }})">
                                        {{ $service['title'] ?? 'Service ' . ($index + 1) }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="tabs-content">
                                @foreach($business->services as $index => $service)
                                    <div class="tab-pane {{ $index === 0 ? 'active' : '' }}" id="tab-{{ $index }}">
                                        <h4>{{ $service['title'] ?? '' }}</h4>
                                        <p>{{ $service['description'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if($business->offers && count($business->offers) > 0)
                    <div class="section">
                        <h3>🎉 Special Offers</h3>
                        <div style="display: grid; gap: 16px; margin-top: 16px;">
                            @foreach($business->offers as $offer)
                                @php
                                    $startDate = \Carbon\Carbon::parse($offer['start_date']);
                                    $endDate = \Carbon\Carbon::parse($offer['end_date']);
                                    $isActive = now()->between($startDate, $endDate);
                                    $isExpired = now()->gt($endDate);
                                    $isScheduled = now()->lt($startDate);
                                @endphp
                                
                                @if($isActive)
                                    <div style="border: 2px solid #10b981; border-radius: 12px; padding: 16px; background: #f0fdf4;">
                                        @if(isset($offer['image_url']))
                                            <img src="{{ $offer['image_url'] }}" alt="Offer" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 12px;">
                                        @endif
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                            <span style="background: #10b981; color: white; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600;">ACTIVE NOW</span>
                                            <div style="text-align: right; font-size: 12px; color: #059669;">
                                                <div>Ends: {{ $endDate->format('M d, Y h:i A') }}</div>
                                                <div style="font-weight: 600;">{{ $endDate->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($business->images && $business->images->count() > 0)
                    <div class="section">
                        <h3>Photo Gallery</h3>
                        <div class="gallery-controls">
                            <div class="view-toggle">
                                <button class="active" onclick="switchGalleryView('carousel')">📷 Carousel</button>
                                <button onclick="switchGalleryView('grid')">🎞️ Grid</button>
                            </div>
                            <span style="color: var(--text-muted); font-size: 14px;">{{ $business->images->count() }} {{ Str::plural('photo', $business->images->count()) }}</span>
                        </div>
                        
                        <div id="galleryCarousel" class="carousel-container">
                            @foreach($business->images as $index => $image)
                                <div class="carousel-slide {{ $index === 0 ? 'active' : '' }}">
                                    <img src="{{ $image->image_url }}" alt="Gallery image {{ $index + 1 }}">
                                </div>
                            @endforeach
                            
                            @if($business->images->count() > 1)
                                <button class="carousel-nav carousel-prev" onclick="changeSlide(-1)">‹</button>
                                <button class="carousel-nav carousel-next" onclick="changeSlide(1)">›</button>
                                
                                <div class="carousel-dots">
                                    @foreach($business->images as $index => $image)
                                        <button class="carousel-dot {{ $index === 0 ? 'active' : '' }}" onclick="goToSlide({{ $index }})"></button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        
                        <div id="galleryGrid" class="gallery-grid" style="display: none;">
                            @foreach($business->images as $image)
                                <img src="{{ $image->image_url }}" alt="Gallery image" onclick="openCarouselFromGrid({{ $loop->index }})">
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div>
                <div class="contact-info">
                    <h3 style="margin-bottom: 20px;">Contact Information</h3>

                    @if($business->phone)
                        <div class="contact-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <div>
                                <strong>Phone</strong>
                                <a href="tel:{{ $business->phone }}">{{ $business->phone }}</a>
                            </div>
                        </div>
                    @endif

                    @if($business->whatsapp)
                        <div class="contact-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <div>
                                <strong>WhatsApp</strong>
                                <a href="https://wa.me/{{ $business->whatsapp }}" target="_blank">{{ $business->whatsapp }}</a>
                            </div>
                        </div>
                    @endif

                    @if($business->email)
                        <div class="contact-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <div>
                                <strong>Email</strong>
                                <a href="mailto:{{ $business->email }}">{{ $business->email }}</a>
                            </div>
                        </div>
                    @endif

                    @if($business->website)
                        <div class="contact-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                            <div>
                                <strong>Website</strong>
                                <a href="{{ $business->website }}" target="_blank">Visit Website</a>
                            </div>
                        </div>
                    @endif

                    @if($business->address)
                        <div class="contact-item">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <div>
                                <strong>Address</strong>
                                {{ $business->address }}
                            </div>
                        </div>
                    @endif

                    @if($business->facebook || $business->instagram || $business->twitter || $business->linkedin)
                        <div class="social-links">
                            @if($business->facebook)
                                <a href="{{ $business->facebook }}" target="_blank" title="Facebook">📘</a>
                            @endif
                            @if($business->instagram)
                                <a href="{{ $business->instagram }}" target="_blank" title="Instagram">📷</a>
                            @endif
                            @if($business->twitter)
                                <a href="{{ $business->twitter }}" target="_blank" title="Twitter">🐦</a>
                            @endif
                            @if($business->linkedin)
                                <a href="{{ $business->linkedin }}" target="_blank" title="LinkedIn">💼</a>
                            @endif
                        </div>
                    @endif

                    @if($business->latitude && $business->longitude)
                        <div style="margin-top: 32px;">
                            <h3 style="margin-bottom: 12px;">Location</h3>
                            <div id="map"></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($business->latitude && $business->longitude)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const map = L.map('map').setView([{{ $business->latitude }}, {{ $business->longitude }}], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        L.marker([{{ $business->latitude }}, {{ $business->longitude }}])
            .addTo(map)
            .bindPopup('<b>{{ $business->name }}</b><br>{{ $business->address }}')
            .openPopup();
    });
</script>
@endif

<script>
function switchTab(index) {
    // Remove active class from all tabs and panes
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    
    // Add active class to selected tab and pane
    document.querySelectorAll('.tab-button')[index].classList.add('active');
    document.getElementById('tab-' + index).classList.add('active');
}

let currentSlide = 0;
const totalSlides = document.querySelectorAll('.carousel-slide').length;

function showSlide(index) {
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    
    if (index >= totalSlides) currentSlide = 0;
    if (index < 0) currentSlide = totalSlides - 1;
    
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    if (slides[currentSlide]) {
        slides[currentSlide].classList.add('active');
    }
    if (dots[currentSlide]) {
        dots[currentSlide].classList.add('active');
    }
}

function changeSlide(direction) {
    currentSlide += direction;
    showSlide(currentSlide);
}

function goToSlide(index) {
    currentSlide = index;
    showSlide(currentSlide);
}

function switchGalleryView(view) {
    const carousel = document.getElementById('galleryCarousel');
    const grid = document.getElementById('galleryGrid');
    const buttons = document.querySelectorAll('.view-toggle button');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    
    if (view === 'carousel') {
        carousel.style.display = 'block';
        grid.style.display = 'none';
        buttons[0].classList.add('active');
    } else {
        carousel.style.display = 'none';
        grid.style.display = 'grid';
        buttons[1].classList.add('active');
    }
}

function openCarouselFromGrid(index) {
    switchGalleryView('carousel');
    goToSlide(index);
    window.scrollTo({ top: document.querySelector('.gallery-controls').offsetTop - 100, behavior: 'smooth' });
}
</script>

@if($relatedBusinesses->count() > 0)
    <div class="container related">
        <h3>Related Businesses</h3>
        <div class="grid">
            @foreach($relatedBusinesses as $related)
                <a href="/business/{{ $related->id }}" class="card">
                    <div class="card-image">
                        @if($related->image_url)
                            <img src="{{ $related->image_url }}" alt="{{ $related->name }}">
                        @else
                            {{ strtoupper(substr($related->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">{{ $related->name }}</h3>
                        <span class="card-category">{{ $related->category->name }}</span>
                        <p class="card-description">{{ $related->description }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif
@endsection
