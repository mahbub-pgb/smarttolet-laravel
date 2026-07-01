{{-- User dashboard tab nav: My Listings | Profile Settings --}}
<div class="dash-tabs">
    <a href="{{ route('dashboard') }}"
       class="dash-tab {{ request()->routeIs('dashboard') || request()->routeIs('dashboard.listings.*') ? 'active' : '' }}">
        🏠 My Listings
    </a>
    <a href="{{ route('dashboard.analytics') }}"
       class="dash-tab {{ request()->routeIs('dashboard.analytics') ? 'active' : '' }}">
        📊 Analytics
    </a>
    <a href="{{ route('dashboard.saved') }}"
       class="dash-tab {{ request()->routeIs('dashboard.saved') ? 'active' : '' }}">
        ❤️ Saved
    </a>
    <a href="{{ route('dashboard.searches') }}"
       class="dash-tab {{ request()->routeIs('dashboard.searches') ? 'active' : '' }}">
        🔎 Searches
    </a>
    <a href="{{ route('dashboard.profile') }}"
       class="dash-tab {{ request()->routeIs('dashboard.profile') ? 'active' : '' }}">
        ⚙️ Profile Settings
    </a>
</div>
