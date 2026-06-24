@extends('layouts.app')

@section('title', 'Complete your profile')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="steps">
                <span class="step done">1. Phone</span>
                <span class="step done">2. Verify</span>
                <span class="step active">3. Profile</span>
            </div>
            <h1>Almost there</h1>
            <p class="sub">Your number <strong>{{ $mobile }}</strong> is verified. Set up your profile.</p>

            <form method="POST" action="{{ route('register.complete') }}">
                @csrf
                <div class="field">
                    <label>Full name</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Your name" required autofocus>
                    @error('name')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Email <span style="font-weight:400">(optional)</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com">
                    @error('email')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="At least 8 characters" required>
                    @error('password')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Confirm password</label>
                    <input type="password" name="password_confirmation" placeholder="Re-enter password" required>
                </div>
                <button type="submit" class="btn btn-block">Create account</button>
            </form>
        </div>
    </div>
@endsection
