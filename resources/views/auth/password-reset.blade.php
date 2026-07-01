@extends('layouts.app')

@section('title', 'Set a new password')

@section('content')
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="steps">
                <span class="step done">1. Phone</span>
                <span class="step done">2. Verify</span>
                <span class="step active">3. New password</span>
            </div>
            <h1>Set a new password</h1>
            <p class="sub">Choose a new password for your account.</p>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul style="margin:0 0 0 18px">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.reset') }}">
                @csrf
                <div class="field">
                    <label>New password</label>
                    <input type="password" name="password" placeholder="At least 8 characters"
                           autocomplete="new-password" required autofocus>
                </div>
                <div class="field">
                    <label>Confirm new password</label>
                    <input type="password" name="password_confirmation" placeholder="Re-enter your password"
                           autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-block">Reset password</button>
            </form>

            <p class="auth-foot"><a href="{{ route('login') }}">Back to log in</a></p>
        </div>
    </div>
@endsection
