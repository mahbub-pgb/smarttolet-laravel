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
                <a href="{{ route('listings.index') }}" class="btn btn-ghost btn-sm">☰ List view</a>
            </div>
            <div id="map" data-maps="{{ $mapsKey ? 'google' : 'leaflet' }}" data-zoom="{{ $mapDefaultZoom }}" data-zoom-pinned="{{ $mapPinnedZoom }}" data-lat="{{ $mapDefaultLat }}" data-lng="{{ $mapDefaultLng }}"></div>
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
