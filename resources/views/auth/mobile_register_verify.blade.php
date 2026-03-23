@extends('layouts.public')

@section('content')
<div class="container">
    <h2>Verify your email</h2>

    @if($errors->any())
        <div class="errors">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('mobile.register.verify.post') }}">
        @csrf
        <input type="hidden" name="email" value="{{ old('email', $email) }}">

        <div>
            <label>OTP</label>
            <input type="text" name="otp" required />
        </div>

        <div>
            <button class="btn" type="submit">Verify</button>
        </div>
    </form>

    <form method="POST" action="{{ route('mobile.register.resend') }}" style="margin-top:10px">
        @csrf
        <input type="hidden" name="email" value="{{ old('email', $email) }}">
        <button class="btn" type="submit">Resend OTP</button>
    </form>
</div>
@endsection
