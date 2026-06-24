@extends('layouts.app')

@section('title', 'Sign up')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="steps">
                <span class="step active">1. Phone</span>
                <span class="step">2. Verify</span>
                <span class="step">3. Profile</span>
            </div>
            <h1>Create your account</h1>
            <p class="sub">Enter your mobile number — we'll text you a verification code.</p>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="field">
                    <label>Mobile number</label>
                    <input type="tel" name="mobile" value="{{ old('mobile') }}" placeholder="01712345678" required autofocus>
                    @error('mobile')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-block">Send verification code</button>
            </form>

            <p class="auth-foot">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
        </div>
    </div>
@endsection
