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
            <div id="map"></div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.MAP_POINTS = @json($points);
    </script>

    @if ($mapsKey)
        <script>
            function initListingsMap() {
                const points = window.MAP_POINTS || [];
                const map = new google.maps.Map(document.getElementById('map'), {
                    center: { lat: 23.8103, lng: 90.4125 }, // Dhaka
                    zoom: 12,
                    mapTypeControl: false,
                    streetViewControl: false,
                });

                const info = new google.maps.InfoWindow();
                const bounds = new google.maps.LatLngBounds();
                const fmt = n => Number(n).toLocaleString();

                points.forEach(p => {
                    const pos = { lat: p.lat, lng: p.lng };
                    const marker = new google.maps.Marker({ position: pos, map, title: p.title });
                    bounds.extend(pos);

                    const facts = [];
                    if (p.bedrooms) facts.push(`🛏 ${p.bedrooms} bed`);
                    if (p.bathrooms) facts.push(`🛁 ${p.bathrooms} bath`);
                    if (p.area_sqft) facts.push(`📐 ${fmt(p.area_sqft)} sqft`);

                    const html =
                        '<div class="map-pop">' +
                        (p.image ? `<img src="${p.image}" alt="">` : '') +
                        '<div class="map-pop-body">' +
                        `<div class="map-pop-price">৳${fmt(p.rent)} <small>/mo</small></div>` +
                        `<div class="map-pop-title">${p.title}</div>` +
                        `<div class="map-pop-meta">📍 ${p.area} · ${p.type}</div>` +
                        (facts.length ? `<div class="map-pop-facts">${facts.join(' · ')}</div>` : '') +
                        `<a class="map-pop-link" href="${p.url}">View details →</a>` +
                        '</div></div>';

                    marker.addListener('click', () => { info.setContent(html); info.open(map, marker); });
                });

                if (points.length) {
                    map.fitBounds(bounds);
                    if (points.length === 1) map.setZoom(15);
                }
            }
        </script>
        <script async src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initListingsMap"></script>
    @else
        {{-- Fallback: Leaflet + OpenStreetMap when no Google Maps key is set. --}}
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            (function () {
                const points = window.MAP_POINTS || [];
                const map = L.map('map');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
                const fmt = n => Number(n).toLocaleString();

                if (points.length) {
                    const markers = points.map(p => {
                        const m = L.marker([p.lat, p.lng]);
                        const facts = [];
                        if (p.bedrooms) facts.push(`🛏 ${p.bedrooms} bed`);
                        if (p.bathrooms) facts.push(`🛁 ${p.bathrooms} bath`);
                        if (p.area_sqft) facts.push(`📐 ${fmt(p.area_sqft)} sqft`);
                        m.bindPopup(
                            '<div class="map-pop">' +
                            (p.image ? `<img src="${p.image}" alt="">` : '') +
                            '<div class="map-pop-body">' +
                            `<div class="map-pop-price">৳${fmt(p.rent)} <small>/mo</small></div>` +
                            `<div class="map-pop-title">${p.title}</div>` +
                            `<div class="map-pop-meta">📍 ${p.area} · ${p.type}</div>` +
                            (facts.length ? `<div class="map-pop-facts">${facts.join(' · ')}</div>` : '') +
                            `<a class="map-pop-link" href="${p.url}">View details →</a>` +
                            '</div></div>'
                        );
                        return m;
                    });
                    const group = L.featureGroup(markers).addTo(map);
                    map.fitBounds(group.getBounds().pad(0.2));
                } else {
                    map.setView([23.8103, 90.4125], 12);
                }
            })();
        </script>
    @endif
@endpush
