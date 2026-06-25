{{--
    Category navigation pills shared by the listings list and map views.
    Each pill filters by a property `type` or a single `occupancy` rule
    (see App\Models\Listing::NAV_CATEGORIES). Links keep the current route
    and query string, only swapping the category filter.
--}}
@php
    $navRoute = $navRoute ?? (\Illuminate\Support\Facades\Route::currentRouteName() ?: 'listings.index');
    // Drop paging + both category params, then re-add the pill's own param.
    $base = request()->except(['page', 'type', 'occupancy']);
    $activeNone = ! request()->filled('type') && ! request()->filled('occupancy');
@endphp

<nav class="cat-nav" aria-label="Browse by category">
    <a href="{{ route($navRoute, $base) }}"
       class="cat-pill @if ($activeNone) is-active @endif">
        <span class="cat-pill-icon">✨</span> All
    </a>
    @foreach (\App\Models\Listing::NAV_CATEGORIES as $cat)
        @php($active = request($cat['param']) === $cat['value'])
        <a href="{{ route($navRoute, array_merge($base, [$cat['param'] => $cat['value']])) }}"
           class="cat-pill @if ($active) is-active @endif">
            <span class="cat-pill-icon">{{ $cat['icon'] }}</span> {{ $cat['label'] }}
        </a>
    @endforeach
</nav>
