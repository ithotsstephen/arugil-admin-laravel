@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Dashboard</h2>
        <p class="muted">Overview of platform activity.</p>
    </div>
</div>

<div class="cards">
    <div class="card">
        <h3>Total Users</h3>
        <p>{{ $stats['users'] }}</p>
    </div>
    <div class="card">
        <h3>Active Businesses</h3>
        <p>{{ $stats['active_businesses'] }}</p>
    </div>
    <div class="card">
        <h3>Pending Approvals</h3>
        <p>{{ $stats['pending_approvals'] }}</p>
    </div>
    <div class="card">
        <h3>Pending Reviews</h3>
        <p>{{ $stats['reviews_pending'] }}</p>
    </div>
    <div class="card">
        <h3>Active Jobs</h3>
        <p>{{ $stats['jobs_active'] }}</p>
    </div>
    <div class="card">
        <h3>Active Ads</h3>
        <p>{{ $stats['ads_active'] }}</p>
    </div>
</div>

<div class="grid-2" style="margin-top: 24px;">
    <div class="card">
        <h3>Monthly Growth</h3>
        <table style="margin-top: 8px;">
            <thead>
            <tr>
                <th>Month</th>
                <th>New Users</th>
            </tr>
            </thead>
            <tbody>
            @foreach($months as $month => $total)
                <tr>
                    <td>{{ $month }}</td>
                    <td>{{ $total }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card">
        <h3>Revenue Summary</h3>
        <p>${{ number_format($revenueSummary['monthly'], 2) }} this month</p>
        <p style="font-size: 16px; font-weight: 500;">${{ number_format($revenueSummary['ytd'], 2) }} YTD</p>
    </div>
</div>
@endsection
