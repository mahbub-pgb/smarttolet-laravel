{{-- User dashboard tab nav: My Listings | Profile Settings --}}
<div class="dash-tabs">
    <a href="{{ route('dashboard') }}"
       class="dash-tab {{ request()->routeIs('dashboard') || request()->routeIs('dashboard.listings.*') ? 'active' : '' }}">
        🏠 My Listings
    </a>
    <a href="{{ route('dashboard.profile') }}"
       class="dash-tab {{ request()->routeIs('dashboard.profile') ? 'active' : '' }}">
        ⚙️ Profile Settings
    </a>
</div>
