@extends('layouts.app')

@section('title', 'Map View')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const points = @json($points);
        const map = L.map('map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        if (points.length) {
            const markers = points.map(p => {
                const m = L.marker([p.lat, p.lng]);
                const href = "{{ url('listings') }}/" + p.slug;
                m.bindPopup(
                    '<div class="map-popup">' +
                    (p.image ? '<img src="' + p.image + '" style="width:100%;height:90px;object-fit:cover;border-radius:6px;margin-bottom:6px">' : '') +
                    '<b>' + p.title + '</b>' +
                    '<span class="price">৳' + Number(p.rent).toLocaleString() + ' / mo</span>' +
                    '<small>' + p.area + ' · ' + p.type + '</small><br>' +
                    '<a href="' + href + '">View details →</a>' +
                    '</div>'
                );
                return m;
            });
            const group = L.featureGroup(markers).addTo(map);
            map.fitBounds(group.getBounds().pad(0.2));
        } else {
            // Default view: Dhaka, Bangladesh.
            map.setView([23.8103, 90.4125], 12);
        }
    </script>
@endpush
