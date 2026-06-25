@extends('layouts.app')

@section('title', 'Map View')

@php($mapsKey = config('geo.google.browser_key'))

@push('head')
    @unless ($mapsKey)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endunless
@endpush

@section('content')
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Explore on the map</h2>
                    <p>{{ $listings->count() }} {{ \Illuminate\Support\Str::plural('listing', $listings->count()) }} plotted. Click a pin for details.</p>
                </div>
                <a href="{{ route('listings.index', request()->except('page')) }}" class="btn btn-ghost btn-sm">☰ List view</a>
            </div>

            {{-- Category + distance + sort controls, all on one line. The category
                 dropdown swaps the active `type`/`occupancy` filter; the rest
                 reload with the chosen rent/beds/radius/sort parameters. --}}
            <form method="GET" action="{{ route('listings.map') }}" class="map-filters" id="map-filters">
                {{-- Preserve the active keyword filters across reloads. --}}
                @foreach (['q', 'area'] as $keep)
                    @if (request()->filled($keep))
                        <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
                    @endif
                @endforeach
                {{-- The category select writes the chosen rule into these before submit. --}}
                <input type="hidden" name="type" id="cat-type" value="{{ request('type') }}">
                <input type="hidden" name="occupancy" id="cat-occupancy" value="{{ request('occupancy') }}">
                <input type="hidden" name="lat" id="near-lat" value="{{ request('lat') }}">
                <input type="hidden" name="lng" id="near-lng" value="{{ request('lng') }}">

                <label class="map-filter-field">
                    Category
                    <select id="category-select">
                        <option value="">All categories</option>
                        @foreach (\App\Models\Listing::NAV_CATEGORIES as $cat)
                            <option value="{{ $cat['param'] }}:{{ $cat['value'] }}"
                                @selected(request($cat['param']) === $cat['value'])>{{ $cat['icon'] }} {{ $cat['label'] }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="map-filter-field">
                    Distance
                    <select name="radius" id="near-radius">
                        @foreach ([1 => 'Near me', 2 => '2 km', 5 => '5 km', 10 => '10 km'] as $km => $label)
                            <option value="{{ $km }}" @selected((string) request('radius', 1) === (string) $km)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="map-filter-field">
                    Min rent
                    <input type="number" name="min_rent" min="0" value="{{ request('min_rent') }}" placeholder="0">
                </label>
                <label class="map-filter-field">
                    Max rent
                    <input type="number" name="max_rent" min="0" value="{{ request('max_rent') }}" placeholder="Any">
                </label>
                <label class="map-filter-field">
                    Beds
                    <select name="bedrooms">
                        <option value="">Any</option>
                        @foreach ([1, 2, 3, 4] as $b)
                            <option value="{{ $b }}" @selected(request('bedrooms') == $b)>{{ $b }}+</option>
                        @endforeach
                    </select>
                </label>
                <label class="map-filter-field">
                    Sort
                    <select name="sort">
                        <option value="">Default</option>
                        <option value="nearest" @selected(request('sort') === 'nearest')>Nearest</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                        <option value="popular" @selected(request('sort') === 'popular')>Most viewed</option>
                    </select>
                </label>

                <button type="submit" class="btn btn-sm">Apply</button>
                @if (request()->hasAny(['lat', 'lng', 'min_rent', 'max_rent', 'bedrooms', 'sort', 'radius']))
                    <a href="{{ route('listings.map', request()->only(['type', 'occupancy'])) }}" class="btn btn-ghost btn-sm" id="map-reset">Reset</a>
                @endif
            </form>

            <div id="map"
                 data-maps="{{ $mapsKey ? 'google' : 'leaflet' }}"
                 data-zoom="{{ $mapDefaultZoom }}"
                 data-zoom-pinned="{{ $mapPinnedZoom }}"
                 data-lat="{{ $mapDefaultLat }}"
                 data-lng="{{ $mapDefaultLng }}"
                 @if ($origin) data-origin-lat="{{ $origin['lat'] }}" data-origin-lng="{{ $origin['lng'] }}" @endif></div>
        </div>
    </section>

    {{-- Data island (non-executable JSON) read by public/js/listing-map.js --}}
    <script type="application/json" id="map-points">@json($points)</script>
@endsection

@push('scripts')
    @unless ($mapsKey)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endunless
    <script src="{{ asset('js/listing-map.js') }}"></script>
    @if ($mapsKey)
        <script async src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initListingsMap"></script>
    @endif
@endpush
