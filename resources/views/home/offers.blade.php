@extends('layouts.public')

@section('title', 'Active Offers')

@section('content')
<section class="hero">
    <div class="container">
        <h2>Active Offers</h2>
        <p>Browse all live promotions from verified businesses.</p>
    </div>
</section>

<div class="container">
    @if($offers->count() === 0)
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
            </svg>
            <h3>No active offers right now</h3>
            <p>Check back soon for new promotions.</p>
        </div>
    @else
        <div class="grid">
            @foreach($offers as $offer)
                @php
                    $business = $offer['business'];
                    $endDate = $offer['end_date'];
                @endphp
                <a class="card" href="{{ route('business.show', $business) }}">
                    <div class="card-image">
                        @if($offer['image_url'])
                            <img src="{{ $offer['image_url'] }}" alt="Offer image">
                        @else
                            🎉
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="card-title">{{ $business->name }}</div>
                        <div class="card-category">{{ $business->category->name }}</div>
                        <p class="card-description">
                            Offer ends {{ $endDate->format('M d, Y h:i A') }} · {{ $endDate->diffForHumans() }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        {{ $offers->links() }}
    @endif
</div>
@endsection
