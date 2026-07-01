@extends('layouts.app')

@section('title', 'Map View')

@php($mapsKey = config('geo.google.browser_key'))

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    @unless ($mapsKey)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
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
                <div class="section-head-actions">
                    <label class="near-me-toggle" for="near-me">
                        <input type="checkbox" id="near-me"> 📍 Near me
                    </label>
                    <a href="{{ route('listings.index', request()->except('page')) }}" class="btn btn-ghost btn-sm">☰ List view</a>
                </div>
            </div>

            {{-- Category + distance + sort controls, all on one line. The category
                 dropdown swaps the active `type`/`occupancy` filter; the rest
                 reload with the chosen rent/beds/radius/sort parameters. --}}
            <form method="GET" action="{{ route('listings.map') }}" class="map-filters" id="map-filters">
                {{-- Preserve the active keyword filter across reloads. --}}
                @if (request()->filled('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                {{-- The category select writes the chosen rule into these before submit. --}}
                <input type="hidden" name="type" id="cat-type" value="{{ request('type') }}">
                <input type="hidden" name="occupancy" id="cat-occupancy" value="{{ request('occupancy') }}">

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
                    Area
                    @php($currentArea = request('area'))
                    @php($areaGroups = config('bd_areas', []))
                    @php($knownAreas = collect($areaGroups)->flatten())
                    <select name="area" class="js-area-select" data-placeholder="Any area">
                        <option value="">Any area</option>
                        {{-- Preserve a typed/custom area that isn't in the curated list. --}}
                        @if ($currentArea && ! $knownAreas->contains($currentArea))
                            <option value="{{ $currentArea }}" selected>{{ $currentArea }}</option>
                        @endif
                        @foreach ($areaGroups as $city => $cityAreas)
                            <optgroup label="{{ $city }}">
                                @foreach ($cityAreas as $area)
                                    <option value="{{ $area }}" @selected($area === $currentArea)>{{ $area }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </label>

                @php($rentStep = 500)
                @php($minRentVal = (int) request('min_rent', 0))
                @php($maxRentVal = request()->filled('max_rent') ? (int) request('max_rent') : $rentCeiling)
                <div class="map-filter-field rent-slider"
                     data-min="0" data-max="{{ $rentCeiling }}" data-step="{{ $rentStep }}">
                    <span class="rent-slider-label">
                        Rent ৳<output id="rent-min-out"></output> – ৳<output id="rent-max-out"></output>
                    </span>
                    <div class="rent-slider-track">
                        <div class="rent-slider-rail"></div>
                        <div class="rent-slider-range" id="rent-range"></div>
                        <input type="range" id="rent-min" min="0" max="{{ $rentCeiling }}"
                               step="{{ $rentStep }}" value="{{ $minRentVal }}">
                        <input type="range" id="rent-max" min="0" max="{{ $rentCeiling }}"
                               step="{{ $rentStep }}" value="{{ $maxRentVal }}">
                    </div>
                    {{-- Actual submitted values (left blank at the extremes so we don't over-filter). --}}
                    <input type="hidden" name="min_rent" id="rent-min-input" value="{{ request('min_rent') }}">
                    <input type="hidden" name="max_rent" id="rent-max-input" value="{{ request('max_rent') }}">
                </div>
                <label class="map-filter-field">
                    Beds
                    <select name="bedrooms">
                        <option value="">Any</option>
                        @foreach ([1, 2, 3, 4, 5] as $b)
                            <option value="{{ $b }}" @selected(request('bedrooms') == $b)>{{ $b }} {{ \Illuminate\Support\Str::plural('bed', $b) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="map-filter-field">
                    Sort
                    <select name="sort">
                        <option value="">Default</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                        <option value="popular" @selected(request('sort') === 'popular')>Most viewed</option>
                    </select>
                </label>

                <div class="map-filter-actions">
                    <button type="submit" class="btn btn-sm">Apply</button>
                    @if (request()->hasAny(['type', 'occupancy', 'q', 'area', 'min_rent', 'max_rent', 'bedrooms', 'sort']))
                        <a href="{{ route('listings.map') }}" class="btn btn-ghost btn-sm" id="map-reset">
                            <span class="map-filter-actions-icon" aria-hidden="true">✕</span> Clear all
                        </a>
                    @endif
                </div>
            </form>

            <div id="map"
                 data-maps="{{ $mapsKey ? 'google' : 'leaflet' }}"
                 data-zoom="{{ $mapDefaultZoom }}"
                 data-zoom-pinned="{{ $mapPinnedZoom }}"
                 data-lat="{{ $mapDefaultLat }}"
                 data-lng="{{ $mapDefaultLng }}"></div>
        </div>
    </section>

    {{-- Data island (non-executable JSON) read by public/js/listing-map.js --}}
    <script type="application/json" id="map-points">@json($points)</script>
@endsection

@push('scripts')
    @unless ($mapsKey)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    @else
        <script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
    @endunless
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/area-select.js') }}"></script>
    <script src="{{ asset('js/listing-map.js') }}"></script>
    <script src="{{ asset('js/rent-slider.js') }}"></script>
    @if ($mapsKey)
        <script async src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initListingsMap"></script>
    @endif
@endpush
