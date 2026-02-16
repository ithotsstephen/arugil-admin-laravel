@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Special Offers</h2>
        <p class="muted">View all business offers and their status.</p>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <form method="GET" action="{{ route('admin.offers.index') }}">
        <div class="filters">
            <select name="status" onchange="this.form.submit()">
                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Offers</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active Now</option>
                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
        </div>
    </form>
</div>

@if(count($allOffers) > 0)
    <div style="display: grid; gap: 16px;">
        @foreach($allOffers as $offer)
            <div class="card" style="display: grid; grid-template-columns: 200px 1fr auto; gap: 20px; align-items: center;">
                <div>
                    @if($offer['image_url'])
                        <img src="{{ $offer['image_url'] }}" alt="Offer" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px;">
                    @else
                        <div style="width: 100%; height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                            🎁
                        </div>
                    @endif
                </div>
                
                <div>
                    <h3 style="margin: 0 0 8px 0; font-size: 18px;">{{ $offer['business_name'] }}</h3>
                    <p class="muted" style="margin: 0 0 8px 0; font-size: 13px;">
                        <span style="display: inline-block; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; margin-right: 8px;">
                            {{ $offer['category'] }}
                        </span>
                    </p>
                    <div style="display: flex; gap: 16px; font-size: 13px; color: var(--muted);">
                        <div>
                            <strong>Start:</strong> {{ $offer['start_date']?->format('M d, Y H:i') ?? 'N/A' }}
                        </div>
                        <div>
                            <strong>End:</strong> {{ $offer['end_date']?->format('M d, Y H:i') ?? 'N/A' }}
                        </div>
                    </div>
                    @if($offer['status'] == 'active')
                        <p style="margin-top: 8px; font-size: 12px; color: #10b981;">
                            ⏰ Expires in {{ $offer['end_date']?->diffForHumans() ?? 'N/A' }}
                        </p>
                    @endif
                </div>
                
                <div style="text-align: right;">
                    @if($offer['status'] == 'active')
                        <span class="badge" style="background: #dcfce7; color: #166534; padding: 6px 12px; font-size: 12px;">✓ Active</span>
                    @elseif($offer['status'] == 'scheduled')
                        <span class="badge" style="background: #fef3c7; color: #92400e; padding: 6px 12px; font-size: 12px;">⏳ Scheduled</span>
                    @else
                        <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 6px 12px; font-size: 12px;">✕ Expired</span>
                    @endif
                    <div style="margin-top: 12px;">
                        <a href="{{ route('admin.businesses.edit', $offer['business_id']) }}" class="btn" style="font-size: 12px; padding: 6px 12px;">
                            Edit Business
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="card" style="text-align: center; padding: 60px 20px; color: var(--muted);">
        <div style="font-size: 64px; margin-bottom: 16px;">🎁</div>
        <h3 style="margin-bottom: 8px;">No offers found</h3>
        <p>Businesses haven't added any special offers yet.</p>
    </div>
@endif

<style>
    .filters select {
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid var(--border);
        min-width: 200px;
    }
</style>
@endsection
