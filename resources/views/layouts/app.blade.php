<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartToLet') — next home</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>

<body>
    @php($user = auth('web')->user())
    <header class="site-header">
        <div class="container nav">
            <a href="{{ route('home') }}" class="brand">Smart<span>ToLet</span></a>
            <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Toggle menu"
                aria-controls="primary-nav" aria-expanded="false">
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </button>
            <nav class="nav-links" id="primary-nav">
                <a href="{{ route('listings.index') }}" class="{{ request()->routeIs('listings.index') ? 'active' : '' }}">Listings</a>
                <a href="{{ route('listings.map') }}" class="{{ request()->routeIs('listings.map') ? 'active' : '' }}">Map</a>
                <a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog.*') ? 'active' : '' }}">Blog</a>
                @foreach ($headerPages ?? [] as $navPage)
                <a href="{{ route('pages.show', $navPage->slug) }}"
                    class="{{ request()->routeIs('pages.show') && request()->route('slug') === $navPage->slug ? 'active' : '' }}">{{ $navPage->title }}</a>
                @endforeach
                @if ($user)
                @if ($user->isStaff())
                <a href="{{ route('admin.dashboard') }}"
                    class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">
                    Admin
                </a>
                @endif
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('notifications.index') }}"
                    class="nav-bell {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
                    aria-label="Notifications" title="Notifications">
                    🔔
                    @if (($webUnreadCount ?? 0) > 0)<span class="nav-bell-badge">{{ $webUnreadCount > 9 ? '9+' : $webUnreadCount }}</span>@endif
                </a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Log out</button>
                </form>
                @else
                <a href="{{ route('login') }}">Log in</a>
                <a href="{{ route('register') }}" class="btn btn-sm">Sign up</a>
                @endif
            </nav>
        </div>
    </header>

    <main>
        @if (session('status'))
        <div class="container" style="padding-top:18px">
            <div class="alert alert-success">{{ session('status') }}</div>
        </div>
        @endif
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="brand" style="color:#fff">Smart<span>ToLet</span></div>
                    <p style="max-width:320px">Find rooms, apartments, and offices to rent near you. Browse verified listings, explore them on the map, and connect directly with owners.</p>
                </div>
                <div>
                    <h4>Explore</h4>
                    <a href="{{ route('listings.index') }}">All Listings</a>
                    <a href="{{ route('listings.map') }}">Map View</a>
                    <a href="{{ route('blog.index') }}">Blog</a>
                    <a href="{{ route('home') }}">Home</a>
                </div>
                <div>
                    <h4>Account</h4>
                    <a href="{{ route('login') }}">Log in</a>
                    <a href="{{ route('register') }}">Sign up</a>
                    @if ($user)<a href="{{ route('dashboard') }}">Dashboard</a>@endif
                </div>
                <div>
                    <h4>Company</h4>
                    @forelse ($footerPages ?? [] as $navPage)
                    <a href="{{ route('pages.show', $navPage->slug) }}">{{ $navPage->title }}</a>
                    @empty
                    <a href="{{ route('home') }}">Home</a>
                    @endforelse
                </div>
            </div>
            <div class="footer-bottom">© {{ date('Y') }} SmartToLet. All rights reserved.</div>
        </div>
    </footer>
    @include('partials.confirm-modal')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('js/nav.js') }}"></script>
    <script src="{{ asset('js/favorites.js') }}"></script>
    <script src="{{ asset('js/confirm-dialog.js') }}"></script>
    @stack('scripts')
</body>

</html>