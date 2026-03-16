@extends('admin.layout')

@section('content')
    <div class="header">
    <div>
        <h2>Geo Fencing</h2>
        <p class="muted">Set the global geofence radius (kilometers).</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.geofence.update') }}">
    @csrf
    @method('PUT')

    <div class="card" style="max-width:500px;">
        <label>Geofence Radius (km)</label>
        <input type="number" name="radius" value="{{ old('radius', isset($setting->radius) ? ($setting->radius / 1000) : '') }}" min="0" step="0.1" placeholder="Enter radius in kilometers (e.g. 1.0)">

        <div style="margin-top:12px; display:flex; gap:8px;">
            <button class="btn btn-primary" type="submit">Save</button>
        </div>
    </div>
</form>

@endsection
