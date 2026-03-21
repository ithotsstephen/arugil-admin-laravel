@extends('layouts.public')

@section('content')
<div class="card">
    <h2>Register</h2>
    @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('mobile.register') }}">
        @csrf
        <input name="full_name" placeholder="Full Name" required>
        <input name="email" placeholder="Email" type="email">
        <input name="phone" placeholder="Enter Your Phone Number">
        <input name="password" placeholder="Password" type="password" required>
        <input name="password_confirmation" placeholder="Confirm Password" type="password" required>
        <button type="submit">Register</button>
    </form>
    <p><a href="{{ route('mobile.login') }}">Login</a></p>
</div>
@endsection
