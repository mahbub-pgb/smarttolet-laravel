@extends('layouts.app')

@section('title', 'Sign up')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Create your account</h1>
            <p class="sub">Join SmartToLet to post listings and save your favourites.</p>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="field">
                    <label>Full name</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Your name" required autofocus>
                    @error('name')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Mobile number</label>
                    <input type="text" name="mobile" value="{{ old('mobile') }}" placeholder="01XXXXXXXXX" required>
                    @error('mobile')<div class="field-error">{{ $message }}</div>@enderror
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

            <p class="auth-foot">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
        </div>
    </div>
@endsection
