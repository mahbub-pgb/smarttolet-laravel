@extends('layouts.app')

@section('title', $listing->title)

@section('content')
    <section class="section">
        <div class="container">
            <p style="color:var(--muted);margin-bottom:18px">
                <a href="{{ route('listings.index') }}">Listings</a> /
                <a href="{{ route('listings.index', ['area' => $listing->area_name]) }}">{{ $listing->area_name }}</a> /
                {{ \Illuminate\Support\Str::limit($listing->title, 40) }}
            </p>

            <div class="detail-grid">
                <div>
                    @php($images = $listing->images ?? [])
                    <div class="gallery">
                        @if (count($images))
                            <img class="lead" src="{{ $images[0]['url'] }}" alt="{{ $listing->title }}">
                            @foreach (array_slice($images, 1, 4) as $img)
                                <img src="{{ $img['url'] }}" alt="">
                            @endforeach
                        @else
                            <div class="card-media lead" style="border-radius:var(--radius)"></div>
                        @endif
                    </div>

                    <div style="margin-top:28px">
                        <span class="badge" style="position:static;display:inline-block;background:var(--brand)">{{ $listing->type }}</span>
                        <h1 style="margin:12px 0 6px;font-size:1.9rem;letter-spacing:-0.02em">{{ $listing->title }}</h1>
                        <p style="color:var(--muted);margin:0">📍 {{ $listing->address }}, {{ $listing->area_name }}</p>

                        <div class="card-meta" style="margin:18px 0;font-size:0.95rem">
                            @if ($listing->bedrooms)<span>🛏 {{ $listing->bedrooms }} bedrooms</span>@endif
                            @if ($listing->bathrooms)<span>🛁 {{ $listing->bathrooms }} bathrooms</span>@endif
                            <span>👁 {{ number_format($listing->view_count) }} views</span>
                        </div>

                        <h3>Description</h3>
                        <div class="prose"><p>{!! nl2br(e($listing->description)) !!}</p></div>

                        @if (!empty($listing->amenities))
                            <h3 style="margin-top:24px">Amenities</h3>
                            <div class="amenities">
                                @foreach ($listing->amenities as $a)<span>{{ $a }}</span>@endforeach
                            </div>
                        @endif

                        @if ($listing->hasLocation())
                            <h3 style="margin-top:24px">Location</h3>
                            <div id="map" style="height:340px"></div>
                        @endif
                    </div>
                </div>

                <aside class="detail-card">
                    <div class="card-price" style="font-size:1.7rem">৳{{ number_format($listing->rent) }} <small>/ month</small></div>
                    <hr style="border:none;border-top:1px solid var(--line);margin:16px 0">
                    <p style="margin:0 0 4px;font-weight:600">Listed by</p>
                    <p style="margin:0 0 16px;color:var(--muted)">{{ $listing->owner->name ?? 'Owner' }}</p>
                    @auth('web')
                        <a href="tel:{{ $listing->owner->mobile }}" class="btn btn-block">📞 {{ $listing->owner->mobile }}</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-block">Log in to see contact</a>
                    @endauth
                    <a href="{{ route('listings.map', ['type' => $listing->type]) }}" class="btn btn-ghost btn-block" style="margin-top:10px">View on map</a>
                </aside>
            </div>

            @if ($related->isNotEmpty())
                <div style="margin-top:56px">
                    <div class="section-head"><div><h2>More in {{ $listing->area_name }}</h2></div></div>
                    <div class="grid">
                        @foreach ($related as $listing)
                            @include('partials.listing-card')
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@if ($listing->hasLocation())
    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const map = L.map('map').setView([{{ $listing->latitude }}, {{ $listing->longitude }}], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
            L.marker([{{ $listing->latitude }}, {{ $listing->longitude }}]).addTo(map)
                .bindPopup(@json($listing->title)).openPopup();
        </script>
    @endpush
@endif
