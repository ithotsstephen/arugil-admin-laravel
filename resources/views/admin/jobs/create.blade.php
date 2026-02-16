@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Add Job</h2>
        <p class="muted">Create a new job listing.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.jobs.store') }}">
    @csrf
    <div class="card" style="max-width: 900px;">
        <h3 style="margin-bottom: 16px;">Job Information</h3>
        
        <label>Business*</label>
        <select name="business_id" required>
            <option value="">Select Business</option>
            @foreach($businesses as $business)
                <option value="{{ $business->id }}" {{ old('business_id') == $business->id ? 'selected' : '' }}>
                    {{ $business->name }}
                </option>
            @endforeach
        </select>
        
        <label>Job Title*</label>
        <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. Sales Manager, Web Developer">
        
        <label>Description</label>
        <textarea name="description" rows="6" placeholder="Job responsibilities, requirements, qualifications...">{{ old('description') }}</textarea>
        
        <label>Salary</label>
        <input type="text" name="salary" value="{{ old('salary') }}" placeholder="e.g. $50,000 - $70,000 per year">
        
        <label>Expiry Date</label>
        <input type="date" name="expiry_date" value="{{ old('expiry_date') }}">
        
        <label>Status*</label>
        <select name="status" required>
            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Expired</option>
        </select>
        
        <div style="display: flex; gap: 8px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary">Create Job</button>
            <a href="{{ route('admin.jobs.index') }}" class="btn">Cancel</a>
        </div>
    </div>
</form>

<style>
    label { display: block; margin-bottom: 6px; font-size: 13px; color: var(--muted); font-weight: 500; }
    input, textarea, select { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 14px; }
    h3 { font-size: 16px; color: var(--text); }
</style>
@endsection
