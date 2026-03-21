@extends('layouts.public')

@section('content')
<div class="card">
    <h2>Forgot Password</h2>
    @if(session('status'))<div class="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('mobile.forgot.send') }}">
        @csrf
        <input name="email" placeholder="Email" type="email" required>
        <button type="submit">Send OTP</button>
    </form>

    <hr>

    <h3>Have an OTP?</h3>
    <form method="POST" action="{{ route('mobile.forgot.verify') }}">
        @csrf
        <input name="email" placeholder="Email" type="email" required>
        <input name="otp" placeholder="OTP" required>
        <input name="password" placeholder="New Password" type="password" required>
        <input name="password_confirmation" placeholder="Confirm Password" type="password" required>
        <button type="submit">Reset Password</button>
    </form>
</div>
@endsection
