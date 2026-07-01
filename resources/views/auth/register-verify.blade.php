@extends('layouts.app')

@section('title', 'Verify your number')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="steps">
                <span class="step done">1. Phone</span>
                <span class="step active">2. Verify</span>
                <span class="step">3. Profile</span>
            </div>
            <h1>Enter the code</h1>
            <p class="sub">We sent a verification code to <strong>{{ $mobile }}</strong>.</p>

            <form method="POST" action="{{ route('register.verify') }}" data-once>
                @csrf
                <div class="field">
                    <label>Verification code</label>
                    <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                           placeholder="● ● ● ● ● ●" class="otp-input" required autofocus>
                    @error('code')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-block" data-busy-text="Verifying…">Verify &amp; continue</button>
            </form>

            <form method="POST" action="{{ route('register.resend') }}" data-once style="margin-top:14px">
                @csrf
                <button type="submit" class="btn btn-ghost btn-block"
                        data-resend-countdown="{{ (int) config('otp.resend_cooldown_seconds', 60) }}">Resend code</button>
            </form>

            <p class="auth-foot"><a href="{{ route('register') }}">← Use a different number</a></p>
        </div>
    </div>
@endsection
