@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="steps">
                <span class="step active">1. Phone</span>
                <span class="step">2. Verify</span>
                <span class="step">3. New password</span>
            </div>
            <h1>Reset your password</h1>
            <p class="sub">Enter your registered mobile number and we'll text you a reset code.</p>

            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.forgot') }}" data-once>
                @csrf
                <div class="field">
                    <label>Mobile number</label>
                    <input type="text" name="mobile" value="{{ old('mobile') }}" inputmode="numeric"
                           placeholder="01XXXXXXXXX" required autofocus>
                </div>
                <button type="submit" class="btn btn-block" data-busy-text="Sending…">Send reset code</button>
            </form>

            <p class="auth-foot">Remembered it? <a href="{{ route('login') }}">Back to log in</a></p>
        </div>
    </div>
@endsection
