<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — SmartToLet</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="admin-body">
    @php($user = auth('web')->user())
    <aside class="admin-sidebar">
        <a href="{{ route('home') }}" class="brand" style="color:#fff;padding:0 22px;display:block;margin-bottom:24px">Smart<span>ToLet</span></a>
        <nav class="admin-nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">📊 Dashboard</a>
            <a href="{{ route('admin.listings.index') }}" class="{{ request()->routeIs('admin.listings.*') ? 'active' : '' }}">🏠 Manage Listings</a>
            <a href="{{ route('admin.settings.sms') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">✉️ SMS Settings</a>
            <div class="admin-nav-sep">Site</div>
            <a href="{{ route('listings.index') }}">🏠 View Listings</a>
            <a href="{{ route('dashboard') }}">👤 My Account</a>
        </nav>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <h1>@yield('heading', 'Dashboard')</h1>
            <div class="admin-user">
                <span>{{ $user->name ?? $user->mobile }} · <strong>{{ $user->role->label() }}</strong></span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm">Log out</button></form>
            </div>
        </header>

        <main class="admin-content">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
