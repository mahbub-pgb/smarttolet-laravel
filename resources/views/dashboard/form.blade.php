@extends('layouts.app')

@section('title', $isEdit ? 'Edit listing' : 'Add listing')

@php
    $selectedAmenities = old('amenities', $listing->amenities ?? []);
    $selectedRules = old('occupancy_rules', $listing->occupancy_rules ?? []);
    $lat = old('latitude', $listing->latitude);
    $lng = old('longitude', $listing->longitude);
    $mapsKey = config('geo.google.browser_key');
    $existingImages = $isEdit ? collect($listing->images ?? [])->pluck('url')->all() : [];
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
                  enctype="multipart/form-data" class="listing-form" id="listing-form">
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
                    <p class="form-hint">Search, use your current location, or click the map to drop a pin. The address and area fill in automatically.</p>

                    <div class="map-toolbar">
                        <input type="text" id="map-search" placeholder="🔍 Search for a place or address…" autocomplete="off">
                        <button type="button" class="btn btn-ghost btn-sm" id="use-location">📍 Use current location</button>
                    </div>

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
                    <legend>Photos</legend>
                    <p class="form-hint">Add up to 10 photos — upload new ones or pick from your library.</p>

                    {{-- Selected-image preview strip (filled by JS) --}}
                    <div id="media-preview" class="media-preview"></div>

                    <button type="button" class="btn btn-ghost" id="open-gallery">🖼️ Add / manage photos</button>

                    {{-- Real inputs submitted with the form --}}
                    <input type="file" id="img-input" name="images[]" accept="image/*" multiple hidden>
                    <div id="picked-inputs" hidden></div>
                    <div id="remove-inputs" hidden></div>

                    <label style="margin-top:16px">Video tour URL (YouTube)
                        <input type="url" name="video_tour_url" value="{{ old('video_tour_url', $listing->video_tour_url) }}" placeholder="https://youtube.com/watch?v=…">
                    </label>
                </fieldset>

                {{-- ===== Publishing ===== --}}
                <div class="form-actions">
                    <button type="submit" name="as_draft" value="1" class="btn btn-ghost">Save as draft</button>
                    <button type="submit" name="as_draft" value="0" class="btn">Submit for review</button>
                </div>

                {{-- ===== Gallery modal ===== --}}
                <div class="modal-overlay" id="gallery-modal" aria-hidden="true">
                    <div class="modal" role="dialog" aria-modal="true" aria-label="Manage photos">
                        <div class="modal-head">
                            <h3>Manage photos</h3>
                            <button type="button" class="modal-close" id="gallery-close" aria-label="Close">✕</button>
                        </div>
                        <div class="modal-tabs">
                            <button type="button" class="modal-tab active" data-tab="upload">⬆️ Upload new</button>
                            <button type="button" class="modal-tab" data-tab="library">🗂️ My library ({{ $mediaLibrary->count() }})</button>
                        </div>
                        <div class="modal-body">
                            <div class="modal-pane active" data-pane="upload">
                                <label class="dropzone" id="dropzone">
                                    <span>Click to choose images, or drag &amp; drop here</span>
                                    <small>JPG, PNG or WebP · up to 5&nbsp;MB each</small>
                                </label>
                            </div>
                            <div class="modal-pane" data-pane="library">
                                @if ($mediaLibrary->isEmpty())
                                    <p class="form-hint">Your library is empty. Upload photos and they will appear here next time.</p>
                                @else
                                    <div class="lib-grid">
                                        @foreach ($mediaLibrary as $media)
                                            <button type="button" class="lib-item" data-id="{{ $media->id }}" data-url="{{ $media->url }}">
                                                <img src="{{ $media->url }}" alt="">
                                                <span class="lib-check">✓</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-foot">
                            <button type="button" class="btn" id="gallery-done">Done</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('head')
    @unless ($mapsKey)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endunless
@endpush

@push('scripts')
    <script>
        window.LISTING_MEDIA = {
            existing: @json($existingImages),
            mapsKey: @json($mapsKey),
        };
    </script>

    {{-- ================= Media gallery modal ================= --}}
    <script>
        (function () {
            const imgInput = document.getElementById('img-input');
            const preview = document.getElementById('media-preview');
            const pickedBox = document.getElementById('picked-inputs');
            const removeBox = document.getElementById('remove-inputs');
            const modal = document.getElementById('gallery-modal');
            const MAX = 10;

            const removed = new Set();                 // existing urls flagged for removal
            const picked = new Map();                  // id -> url (library)
            const existing = (window.LISTING_MEDIA.existing || []);

            function objectUrls() { return imgInput.files ? Array.from(imgInput.files) : []; }
            function total() {
                return existing.filter(u => !removed.has(u)).length + picked.size + objectUrls().length;
            }

            function tile(src, onRemove, badge) {
                const d = document.createElement('div');
                d.className = 'media-thumb';
                d.innerHTML = `<img src="${src}" alt="">` +
                    (badge ? `<span class="media-badge">${badge}</span>` : '') +
                    `<button type="button" class="media-x" aria-label="Remove">✕</button>`;
                d.querySelector('.media-x').addEventListener('click', onRemove);
                return d;
            }

            function syncPickedInputs() {
                pickedBox.innerHTML = '';
                picked.forEach((url, id) => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'picked[]'; i.value = id;
                    pickedBox.appendChild(i);
                });
            }

            function syncRemoveInputs() {
                removeBox.innerHTML = '';
                removed.forEach(url => {
                    const i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'remove_images[]'; i.value = url;
                    removeBox.appendChild(i);
                });
            }

            function removeUpload(idx) {
                const dt = new DataTransfer();
                objectUrls().forEach((f, i) => { if (i !== idx) dt.items.add(f); });
                imgInput.files = dt.files;
                render();
            }

            function render() {
                preview.innerHTML = '';
                existing.filter(u => !removed.has(u)).forEach(url => {
                    preview.appendChild(tile(url, () => { removed.add(url); syncRemoveInputs(); render(); }, 'saved'));
                });
                picked.forEach((url, id) => {
                    preview.appendChild(tile(url, () => { picked.delete(id); syncPickedInputs(); render(); refreshLibSelection(); }, 'library'));
                });
                objectUrls().forEach((file, idx) => {
                    preview.appendChild(tile(URL.createObjectURL(file), () => removeUpload(idx), 'new'));
                });
                if (!preview.children.length) {
                    preview.innerHTML = '<p class="form-hint" style="margin:0">No photos added yet.</p>';
                }
            }

            // ---- Modal open/close + tabs ----
            function openModal() { modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); }
            function closeModal() { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); }
            document.getElementById('open-gallery').addEventListener('click', openModal);
            document.getElementById('gallery-close').addEventListener('click', closeModal);
            document.getElementById('gallery-done').addEventListener('click', closeModal);
            modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

            modal.querySelectorAll('.modal-tab').forEach(btn => btn.addEventListener('click', () => {
                modal.querySelectorAll('.modal-tab').forEach(b => b.classList.remove('active'));
                modal.querySelectorAll('.modal-pane').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                modal.querySelector(`.modal-pane[data-pane="${btn.dataset.tab}"]`).classList.add('active');
            }));

            // ---- Upload (dropzone) ----
            const dropzone = document.getElementById('dropzone');
            dropzone.addEventListener('click', () => imgInput.click());
            ['dragover', 'dragenter'].forEach(ev => dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.add('over'); }));
            ['dragleave', 'drop'].forEach(ev => dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.remove('over'); }));
            dropzone.addEventListener('drop', e => {
                const dt = new DataTransfer();
                objectUrls().forEach(f => dt.items.add(f));
                Array.from(e.dataTransfer.files).forEach(f => { if (f.type.startsWith('image/')) dt.items.add(f); });
                imgInput.files = dt.files;
                enforceMax(); render();
            });
            imgInput.addEventListener('change', () => { enforceMax(); render(); });

            function enforceMax() {
                if (total() <= MAX) return;
                const allowed = Math.max(0, MAX - (existing.filter(u => !removed.has(u)).length + picked.size));
                const dt = new DataTransfer();
                objectUrls().slice(0, allowed).forEach(f => dt.items.add(f));
                imgInput.files = dt.files;
                alert(`You can add at most ${MAX} photos.`);
            }

            // ---- Library selection ----
            function refreshLibSelection() {
                modal.querySelectorAll('.lib-item').forEach(it => {
                    it.classList.toggle('selected', picked.has(it.dataset.id));
                });
            }
            modal.querySelectorAll('.lib-item').forEach(it => it.addEventListener('click', () => {
                const id = it.dataset.id;
                if (picked.has(id)) {
                    picked.delete(id);
                } else {
                    if (total() >= MAX) { alert(`You can add at most ${MAX} photos.`); return; }
                    picked.set(id, it.dataset.url);
                }
                syncPickedInputs(); refreshLibSelection(); render();
            }));

            render();
        })();
    </script>

    {{-- ================= Location map picker ================= --}}
    @if ($mapsKey)
    <script>
        function initListingMap() {
            const el = document.getElementById('pick-map');
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const areaInput = document.getElementById('area_name');
            const addressInput = document.getElementById('address');
            const startLat = parseFloat(el.dataset.lat) || 23.8103;
            const startLng = parseFloat(el.dataset.lng) || 90.4125;
            const hasPin = !!(el.dataset.lat && el.dataset.lng);

            const map = new google.maps.Map(el, {
                center: { lat: startLat, lng: startLng },
                zoom: hasPin ? 16 : 12,
                mapTypeControl: false,
                streetViewControl: false,
            });
            const marker = new google.maps.Marker({ map, position: { lat: startLat, lng: startLng }, draggable: true });
            const geocoder = new google.maps.Geocoder();
            if (hasPin) { latInput.value = startLat.toFixed(7); lngInput.value = startLng.toFixed(7); }

            function setPin(lat, lng, doGeocode) {
                latInput.value = (+lat).toFixed(7);
                lngInput.value = (+lng).toFixed(7);
                marker.setPosition({ lat: +lat, lng: +lng });
                map.panTo({ lat: +lat, lng: +lng });
                if (doGeocode) reverseGeocode(lat, lng);
            }

            function reverseGeocode(lat, lng) {
                geocoder.geocode({ location: { lat: +lat, lng: +lng } }, (results, status) => {
                    if (status !== 'OK' || !results[0]) return;
                    addressInput.value = results[0].formatted_address || addressInput.value;
                    const comp = results[0].address_components || [];
                    const area = comp.find(c => c.types.includes('sublocality') || c.types.includes('neighborhood'))
                        || comp.find(c => c.types.includes('locality'));
                    if (area) areaInput.value = area.long_name;
                });
            }

            marker.addListener('dragend', e => setPin(e.latLng.lat(), e.latLng.lng(), true));
            map.addListener('click', e => setPin(e.latLng.lat(), e.latLng.lng(), true));

            // Search box (Places Autocomplete).
            const search = document.getElementById('map-search');
            if (search && google.maps.places) {
                const ac = new google.maps.places.Autocomplete(search, { fields: ['geometry', 'formatted_address'] });
                ac.addListener('place_changed', () => {
                    const p = ac.getPlace();
                    if (p.geometry) { map.setZoom(16); setPin(p.geometry.location.lat(), p.geometry.location.lng(), true); }
                });
            }

            // Current location.
            document.getElementById('use-location').addEventListener('click', () => {
                if (!navigator.geolocation) { alert('Geolocation is not supported by your browser.'); return; }
                navigator.geolocation.getCurrentPosition(
                    pos => { map.setZoom(16); setPin(pos.coords.latitude, pos.coords.longitude, true); },
                    () => alert('Could not get your location. Please allow location access or pick on the map.')
                );
            });
        }
    </script>
    <script async src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=initListingMap"></script>
    @else
    {{-- Fallback: Leaflet + OSM when no Google Maps key is configured. --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        (function () {
            const el = document.getElementById('pick-map');
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const areaInput = document.getElementById('area_name');
            const addressInput = document.getElementById('address');
            const startLat = parseFloat(el.dataset.lat) || 23.8103;
            const startLng = parseFloat(el.dataset.lng) || 90.4125;
            const hasPin = !!(el.dataset.lat && el.dataset.lng);

            const map = L.map(el).setView([startLat, startLng], hasPin ? 16 : 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
            const marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
            if (hasPin) { latInput.value = startLat.toFixed(7); lngInput.value = startLng.toFixed(7); }

            function reverseGeocode(lat, lng) {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                    .then(r => r.json())
                    .then(d => {
                        if (d.display_name) addressInput.value = d.display_name;
                        const a = d.address || {};
                        const area = a.suburb || a.neighbourhood || a.city_district || a.city || a.town || a.village;
                        if (area) areaInput.value = area;
                    }).catch(() => {});
            }
            function setPin(lat, lng) { latInput.value = (+lat).toFixed(7); lngInput.value = (+lng).toFixed(7); marker.setLatLng([lat, lng]); reverseGeocode(lat, lng); }
            marker.on('dragend', e => { const p = e.target.getLatLng(); setPin(p.lat, p.lng); });
            map.on('click', e => setPin(e.latlng.lat, e.latlng.lng));
            document.getElementById('use-location').addEventListener('click', () => {
                if (!navigator.geolocation) return;
                navigator.geolocation.getCurrentPosition(pos => { map.setView([pos.coords.latitude, pos.coords.longitude], 16); setPin(pos.coords.latitude, pos.coords.longitude); });
            });
        })();
    </script>
    @endif
@endpush
