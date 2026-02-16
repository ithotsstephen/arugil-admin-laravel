@extends('auth.layout')

@section('content')
<div class="card">
    <h1>Admin Login</h1>
    <p>Sign in to manage Arugil Admin.</p>
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required>

        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>

        @error('email')
            <div class="error">{{ $message }}</div>
        @enderror

        <button type="submit">Login</button>
    </form>
</div>
@endsection
