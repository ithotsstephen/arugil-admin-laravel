@extends('layouts.public')

@section('title', 'Delete Account')
@section('hide_public_header', '1')

@section('content')
<section style="padding: 56px 20px;">
    <div style="max-width: 760px; margin: 0 auto 28px; text-align: center;">
        <img src="{{ asset('splash-logo.png') }}" alt="Arugil" style="width: 112px; height: 112px; object-fit: contain; display: inline-block;">
    </div>

    <div style="max-width: 760px; margin: 0 auto; background: var(--card); border: 1px solid var(--border); border-radius: 18px; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08); overflow: hidden;">
        <div style="padding: 32px; background: linear-gradient(135deg, #fee2e2 0%, #fff7ed 100%); border-bottom: 1px solid var(--border);">
            <p style="margin: 0 0 8px; font-size: 12px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #b91c1c;">Arugil account removal</p>
            <h2 style="margin: 0 0 10px; font-size: 34px; line-height: 1.15;">Delete your account</h2>
            <p style="margin: 0; color: var(--text-muted); max-width: 560px;">Use this page to permanently remove your Arugil account and associated app access. This action cannot be undone.</p>
        </div>

        <div style="padding: 32px; display: grid; gap: 28px;">
            @if (session('status'))
                <div style="padding: 14px 16px; border-radius: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="padding: 14px 16px; border-radius: 12px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <h3 style="margin: 0 0 10px; font-size: 20px;">What gets deleted</h3>
                <p style="margin: 0 0 12px; color: var(--text-muted);">Deleting your account removes your user record and revokes access tokens. Related business data and uploaded business media owned by your account are also removed by the system.</p>
            </div>

            <form method="POST" action="{{ route('account.delete.destroy') }}" style="display: grid; gap: 16px;">
                @csrf
                <div>
                    <label for="login" style="display: block; margin-bottom: 6px; font-weight: 600;">Email or phone</label>
                    <input id="login" name="login" type="text" value="{{ old('login') }}" required style="width: 100%; padding: 13px 14px; border-radius: 12px; border: 1px solid var(--border); font-size: 15px;">
                </div>

                <div>
                    <label for="current_password" style="display: block; margin-bottom: 6px; font-weight: 600;">Password</label>
                    <input id="current_password" name="current_password" type="password" required style="width: 100%; padding: 13px 14px; border-radius: 12px; border: 1px solid var(--border); font-size: 15px;">
                </div>

                <button type="submit" style="padding: 14px 18px; border: none; border-radius: 12px; background: #dc2626; color: #fff; font-size: 15px; font-weight: 700; cursor: pointer;">Delete Account</button>
            </form>

            <div style="padding: 16px 18px; border-radius: 12px; background: #f8fafc; border: 1px solid var(--border); color: var(--text-muted);">
                If you signed up using a social login and do not have a password, contact support from the app so your identity can be verified before deletion.
            </div>
        </div>
    </div>
</section>
@endsection