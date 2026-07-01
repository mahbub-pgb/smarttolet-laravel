@extends('layouts.app')

@section('title', $listing->title)

@php($mapsKey = config('geo.google.browser_key'))
@php($embed = $listing->youtubeEmbedUrl())

@section('content')
    <section class="section">
        <div class="container">
            @if (!empty($isPreview))
                <div class="alert alert-warning">
                    👁️ <strong>Preview.</strong> This listing is <strong>{{ $listing->status }}</strong> and not visible to the public yet.
                </div>
            @endif

            <p style="color:var(--muted);margin-bottom:18px">
                <a href="{{ route('listings.index') }}">Listings</a> /
                <a href="{{ route('listings.index', ['area' => $listing->area_name]) }}">{{ $listing->area_name }}</a> /
                {{ \Illuminate\Support\Str::limit($listing->title, 40) }}
            </p>

            <div class="detail-grid">
                <div>
                    @php($images = $listing->images ?? [])

                    {{-- ===== Gallery ===== --}}
                    <div class="gallery2">
                        @if (count($images))
                            <div class="gallery2-main">
                                <img id="gallery-lead" src="{{ $images[0]['url'] }}" alt="{{ $listing->title }}">
                            </div>
                            @if (count($images) > 1)
                                <div class="gallery2-thumbs">
                                    @foreach ($images as $i => $img)
                                        <button type="button" class="gthumb {{ $i === 0 ? 'active' : '' }}" data-src="{{ $img['url'] }}">
                                            <img src="{{ $img['url'] }}" alt="">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="card-media lead" style="border-radius:var(--radius);height:340px"></div>
                        @endif
                    </div>

                    <div style="margin-top:28px">
                        <span class="badge" style="position:static;display:inline-block;background:var(--brand)">{{ $listing->type }}</span>
                        <h1 style="margin:12px 0 6px;font-size:1.9rem;letter-spacing:-0.02em">{{ $listing->title }}</h1>
                        <p style="color:var(--muted);margin:0">📍 {{ $listing->address }}, {{ $listing->area_name }}</p>

                        <div class="card-meta" style="margin:18px 0;font-size:0.95rem">
                            @if ($listing->bedrooms)<span>🛏 {{ $listing->bedrooms }} bedrooms</span>@endif
                            @if ($listing->bathrooms)<span>🛁 {{ $listing->bathrooms }} bathrooms</span>@endif
                            @if ($listing->area_sqft)<span>📐 {{ number_format($listing->area_sqft) }} sq ft</span>@endif
                            <span>👁 {{ number_format($listing->view_count) }} views</span>
                        </div>

                        <h3>Description</h3>
                        <div class="prose"><p>{!! nl2br(e($listing->description)) !!}</p></div>

                        {{-- ===== Video tour ===== --}}
                        @if ($embed)
                            <h3 style="margin-top:24px">Video tour</h3>
                            <div class="video-embed">
                                <iframe src="{{ $embed }}" title="Video tour" frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            </div>
                        @endif

                        @if (!empty($listing->amenities))
                            <h3 style="margin-top:24px">Amenities</h3>
                            <div class="amenities">
                                @foreach ($listing->amenities as $a)<span>{{ \App\Models\Listing::AMENITIES[$a] ?? ucfirst(str_replace('_', ' ', $a)) }}</span>@endforeach
                            </div>
                        @endif

                        @if (!empty($listing->occupancy_rules))
                            <h3 style="margin-top:24px">Occupancy &amp; Rules</h3>
                            <div class="amenities">
                                @foreach ($listing->occupancy_rules as $r)<span>{{ \App\Models\Listing::OCCUPANCY_RULES[$r] ?? ucfirst(str_replace('_', ' ', $r)) }}</span>@endforeach
                            </div>
                        @endif

                        @if ($listing->hasLocation())
                            <div class="loc-head" style="margin-top:24px">
                                <h3 style="margin:0">Location</h3>
                                <button type="button" id="get-directions" class="btn btn-sm">🧭 Get directions</button>
                            </div>
                            <div id="map" class="detail-map"
                                 data-maps="{{ $mapsKey ? 'google' : 'leaflet' }}"
                                 data-lat="{{ $listing->latitude }}" data-lng="{{ $listing->longitude }}"
                                 data-zoom="{{ $mapPinnedZoom }}"
                                 data-title="{{ $listing->title }}"></div>
                            <p id="dir-info" class="form-hint" style="margin-top:8px;display:none"></p>
                        @endif
                    </div>
                </div>

                <aside class="detail-card">
                    <div class="card-price" style="font-size:1.7rem">৳{{ number_format($listing->rent) }} <small>/ month</small></div>
                    @if ($listing->advance_amount)
                        <p style="margin:4px 0 0;color:var(--muted)">Advance: ৳{{ number_format($listing->advance_amount) }}</p>
                    @endif
                    <hr style="border:none;border-top:1px solid var(--line);margin:16px 0">
                    <p style="margin:0 0 4px;font-weight:600">Listed by</p>
                    <p style="margin:0 0 16px;color:var(--muted)">{{ $listing->owner->name ?? 'Owner' }}</p>
                    @auth('web')
                        @if ($contactRevealed)
                            <a href="tel:{{ $listing->owner->mobile }}" class="btn btn-block">📞 {{ $listing->owner->mobile }}</a>
                        @else
                            {{-- Glassy locked number: the real value is fetched (and saved) only on click. --}}
                            <div class="reveal-contact" id="reveal-contact"
                                 data-url="{{ route('listings.reveal-contact', $listing) }}">
                                <a href="#" class="btn btn-block reveal-number" tabindex="-1" aria-hidden="true">📞 01XXX-XXXXXX</a>
                                <button type="button" class="reveal-overlay">🔒 Click to show number</button>
                            </div>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn btn-block">Log in to see contact</a>
                    @endauth

                    @auth('web')
                        @php($isFav = in_array($listing->id, $favoriteIds ?? []))
                        <button type="button"
                            class="btn btn-ghost btn-block fav-btn fav-wide {{ $isFav ? 'is-fav' : '' }}"
                            style="margin-top:10px"
                            data-url="{{ route('favorites.toggle', $listing) }}"
                            data-label-on="❤️ Saved"
                            data-label-off="🤍 Save to favourites"
                            aria-pressed="{{ $isFav ? 'true' : 'false' }}">{{ $isFav ? '❤️ Saved' : '🤍 Save to favourites' }}</button>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ghost btn-block" style="margin-top:10px">🤍 Log in to save</a>
                    @endauth

                    @if ($listing->hasLocation())
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $listing->latitude }},{{ $listing->longitude }}"
                           target="_blank" rel="noopener" class="btn btn-ghost btn-block" style="margin-top:10px">🧭 Directions in Google Maps</a>
                    @endif
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

    {{-- ===== Lightbox ===== --}}
    <div class="lightbox" id="lightbox" aria-hidden="true">
        <button class="lightbox-close" id="lightbox-close" aria-label="Close">✕</button>
        <button class="lightbox-nav lightbox-prev" id="lightbox-prev" aria-label="Previous image">‹</button>
        <img id="lightbox-img" src="" alt="">
        <button class="lightbox-nav lightbox-next" id="lightbox-next" aria-label="Next image">›</button>
    </div>
@endsection

@push('head')
    @if ($listing->hasLocation() && ! $mapsKey)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endif
@endpush

@push('scripts')
    @if ($listing->hasLocation() && ! $mapsKey)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endif
    <script src="{{ asset('js/listing-show.js') }}"></script>
    @if ($listing->hasLocation() && $mapsKey)
        <script async src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&callback=initShowMap"></script>
    @endif
@endpush
