@extends('layouts.public')

@section('content')
<div class="card">
    <h2>Login</h2>
    @if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('mobile.login') }}">
        @csrf
        <input name="email" placeholder="Email">
        <input name="phone" placeholder="Enter Your Phone Number">
        <input name="password" placeholder="Password" type="password" required>
        <button type="submit">Login</button>
    </form>
    <p><a href="{{ route('mobile.register') }}">Register</a> · <a href="{{ route('mobile.forgot') }}">Forgot password</a></p>
</div>
@endsection
