@extends('layouts.app')

@section('title', 'Log in')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Welcome back</h1>
            <p class="sub">Log in to manage your listings and contact owners.</p>

            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="field">
                    <label>Mobile or email</label>
                    <input type="text" name="login" value="{{ old('login') }}" placeholder="01XXXXXXXXX or you@example.com" required autofocus>
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:18px">
                    <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;color:var(--muted)">
                        <input type="checkbox" name="remember" value="1"> Remember me
                    </label>
                    <a href="{{ route('password.forgot') }}" style="font-size:0.9rem">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-block">Log in</button>
            </form>

            <p class="auth-foot">Don't have an account? <a href="{{ route('register') }}">Sign up</a></p>
        </div>
    </div>
@endsection
