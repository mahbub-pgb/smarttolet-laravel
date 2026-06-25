@extends('layouts.app')

@section('title', $isEdit ? 'Edit listing' : 'Add listing')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@php
    $selectedAmenities = old('amenities', $listing->amenities ?? []);
    $selectedRules = old('occupancy_rules', $listing->occupancy_rules ?? []);
    $lat = old('latitude', $listing->latitude);
    $lng = old('longitude', $listing->longitude);
@endphp

@section('content')
    <section class="section">
        <div class="container container-narrow">
            <div class="section-head">
                <div>
                    <h2>{{ $isEdit ? 'Edit listing' : 'Add a new listing' }}</h2>
                    <p>Your listing is reviewed by an admin before it appears publicly.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm">← Back</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-error">
                    <strong>Please fix the following:</strong>
                    <ul style="margin:6px 0 0 18px">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST"
                  action="{{ $isEdit ? route('dashboard.listings.update', $listing) : route('dashboard.listings.store') }}"
                  enctype="multipart/form-data" class="listing-form">
                @csrf
                @if ($isEdit) @method('PUT') @endif

                {{-- ===== Basic Info ===== --}}
                <fieldset class="form-card">
                    <legend>Basic Info</legend>

                    <div class="form-row">
                        <label>Property type *
                            <select name="type" required>
                                <option value="">Select type…</option>
                                @foreach (\App\Models\Listing::TYPES as $type)
                                    <option value="{{ $type }}" @selected(old('type', $listing->type) === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="form-row">
                        <label>Title *
                            <input type="text" name="title" maxlength="160" required value="{{ old('title', $listing->title) }}" placeholder="e.g. Spacious 2-bed apartment in Dhanmondi">
                        </label>
                    </div>

                    <div class="form-row">
                        <label>Description *
                            <textarea name="description" rows="5" maxlength="5000" required placeholder="Describe the property, neighbourhood, terms…">{{ old('description', $listing->description) }}</textarea>
                        </label>
                    </div>

                    <div class="form-grid-3">
                        <label>Monthly rent (৳) *
                            <input type="number" name="rent" min="0" required value="{{ old('rent', $listing->rent) }}">
                        </label>
                        <label>Advance amount (৳)
                            <input type="number" name="advance_amount" min="0" value="{{ old('advance_amount', $listing->advance_amount) }}">
                        </label>
                        <label>Available from
                            <input type="date" name="available_from" value="{{ old('available_from', optional($listing->available_from)->format('Y-m-d')) }}">
                        </label>
                    </div>
                </fieldset>

                {{-- ===== Location ===== --}}
                <fieldset class="form-card">
                    <legend>Location</legend>
                    <p class="form-hint">Drag the pin to the property. The address and area are filled in automatically.</p>

                    <div id="pick-map" class="pick-map" data-lat="{{ $lat }}" data-lng="{{ $lng }}"></div>

                    <input type="hidden" name="latitude" id="latitude" value="{{ $lat }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ $lng }}">

                    <div class="form-grid-2">
                        <label>Area
                            <input type="text" name="area_name" id="area_name" maxlength="120" value="{{ old('area_name', $listing->area_name) }}" placeholder="e.g. Dhanmondi">
                        </label>
                        <label>Address
                            <input type="text" name="address" id="address" maxlength="255" value="{{ old('address', $listing->address) }}" placeholder="Auto-filled from the map">
                        </label>
                    </div>
                </fieldset>

                {{-- ===== Details ===== --}}
                <fieldset class="form-card">
                    <legend>Details</legend>
                    <div class="form-grid-3">
                        <label>Bedrooms<input type="number" name="bedrooms" min="0" max="50" value="{{ old('bedrooms', $listing->bedrooms) }}"></label>
                        <label>Bathrooms<input type="number" name="bathrooms" min="0" max="50" value="{{ old('bathrooms', $listing->bathrooms) }}"></label>
                        <label>Area (sq ft)<input type="number" name="area_sqft" min="0" value="{{ old('area_sqft', $listing->area_sqft) }}"></label>
                        <label>Balconies<input type="number" name="balconies" min="0" max="50" value="{{ old('balconies', $listing->balconies) }}"></label>
                        <label>Floor number<input type="number" name="floor_number" min="-5" max="200" value="{{ old('floor_number', $listing->floor_number) }}"></label>
                        <label>Building floors<input type="number" name="building_floors" min="0" max="200" value="{{ old('building_floors', $listing->building_floors) }}"></label>
                    </div>
                </fieldset>

                {{-- ===== Amenities ===== --}}
                <fieldset class="form-card">
                    <legend>Amenities</legend>
                    <div class="check-grid">
                        @foreach (\App\Models\Listing::AMENITIES as $key => $label)
                            <label class="check-pill">
                                <input type="checkbox" name="amenities[]" value="{{ $key }}" @checked(in_array($key, $selectedAmenities, true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- ===== Occupancy & Rules ===== --}}
                <fieldset class="form-card">
                    <legend>Occupancy &amp; Rules</legend>
                    <div class="check-grid">
                        @foreach (\App\Models\Listing::OCCUPANCY_RULES as $key => $label)
                            <label class="check-pill">
                                <input type="checkbox" name="occupancy_rules[]" value="{{ $key }}" @checked(in_array($key, $selectedRules, true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- ===== Media ===== --}}
                <fieldset class="form-card">
                    <legend>Media</legend>

                    @if ($isEdit && !empty($listing->images))
                        <p class="form-hint">Current images — tick to remove on save.</p>
                        <div class="thumb-grid">
                            @foreach ($listing->images as $image)
                                <label class="thumb">
                                    <img src="{{ $image['url'] }}" alt="">
                                    <span class="thumb-remove"><input type="checkbox" name="remove_images[]" value="{{ $image['url'] }}"> Remove</span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <label>Upload images (up to 10)
                        <input type="file" name="images[]" accept="image/*" multiple>
                    </label>

                    @if ($mediaLibrary->isNotEmpty())
                        <p class="form-hint" style="margin-top:14px">Or pick from your media library</p>
                        <div class="thumb-grid">
                            @foreach ($mediaLibrary as $media)
                                <label class="thumb thumb-pick">
                                    <img src="{{ $media->url }}" alt="">
                                    <span class="thumb-remove"><input type="checkbox" name="picked[]" value="{{ $media->id }}" @checked(in_array($media->id, old('picked', []))) > Use</span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <label style="margin-top:14px">Video tour URL (YouTube)
                        <input type="url" name="video_tour_url" value="{{ old('video_tour_url', $listing->video_tour_url) }}" placeholder="https://youtube.com/watch?v=…">
                    </label>
                </fieldset>

                {{-- ===== Publishing ===== --}}
                <div class="form-actions">
                    <button type="submit" name="as_draft" value="1" class="btn btn-ghost">Save as draft</button>
                    <button type="submit" name="as_draft" value="0" class="btn">Submit for review</button>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            const el = document.getElementById('pick-map');
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const areaInput = document.getElementById('area_name');
            const addressInput = document.getElementById('address');

            const startLat = parseFloat(el.dataset.lat) || 23.8103;   // Dhaka
            const startLng = parseFloat(el.dataset.lng) || 90.4125;
            const hasPin = !!(el.dataset.lat && el.dataset.lng);

            const map = L.map(el).setView([startLat, startLng], hasPin ? 16 : 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);

            function reverseGeocode(lat, lng) {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(r => r.json())
                    .then(d => {
                        if (d.display_name && !addressInput.value) addressInput.value = d.display_name;
                        const a = d.address || {};
                        const area = a.suburb || a.neighbourhood || a.city_district || a.city || a.town || a.village;
                        if (area && !areaInput.value) areaInput.value = area;
                    })
                    .catch(() => {});
            }

            function setPin(lat, lng, geocode) {
                latInput.value = lat.toFixed(7);
                lngInput.value = lng.toFixed(7);
                marker.setLatLng([lat, lng]);
                if (geocode) { addressInput.value = ''; areaInput.value = ''; reverseGeocode(lat, lng); }
            }

            marker.on('dragend', e => { const p = e.target.getLatLng(); setPin(p.lat, p.lng, true); });
            map.on('click', e => setPin(e.latlng.lat, e.latlng.lng, true));

            if (hasPin) { latInput.value = startLat.toFixed(7); lngInput.value = startLng.toFixed(7); }
        })();
    </script>
@endpush
