@extends('layouts.app')

@section('title', $isEdit ? 'Edit listing' : 'Add listing')

@php
    $selectedAmenities = old('amenities', $listing->amenities ?? []);
    $selectedRules = old('occupancy_rules', $listing->occupancy_rules ?? []);
    // New listings default their pin to the owner's saved profile location (if
    // any) so they don't have to re-pick it every time.
    $prefillFromProfile = ! $isEdit && $listing->latitude === null && $user->latitude !== null;
    $lat = old('latitude', $listing->latitude ?? (! $isEdit ? $user->latitude : null));
    $lng = old('longitude', $listing->longitude ?? (! $isEdit ? $user->longitude : null));
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

            @if ($isEdit && $listing->rejections->isNotEmpty())
                <div class="alert alert-warning">
                    <strong>
                        @if ($listing->status === \App\Models\Listing::STATUS_REJECTED)
                            This listing was rejected — fix the points below and resubmit for review.
                        @else
                            Rejection history
                        @endif
                    </strong>
                    <ul class="reject-history">
                        @foreach ($listing->rejections as $rejection)
                            <li>
                                <span class="reject-when">{{ $rejection->created_at->format('d M Y, h:i A') }}</span>
                                {{ $rejection->reason }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

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
                    @if ($prefillFromProfile)
                        <p class="form-hint" style="color:var(--brand)">📍 Starting from your saved profile location — move the pin if this listing is elsewhere.</p>
                    @endif

                    <div class="map-toolbar">
                        <input type="text" id="map-search" placeholder="🔍 Search for a place or address…" autocomplete="off">
                        <button type="button" class="btn btn-ghost btn-sm" id="use-location">📍 Use current location</button>
                    </div>

                    <div id="pick-map" class="pick-map" data-maps="{{ $mapsKey ? 'google' : 'leaflet' }}" data-lat="{{ $lat }}" data-lng="{{ $lng }}"></div>

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
                    <p class="form-hint">Add up to 10 photos — upload new ones or pick from your library. Large images are compressed automatically before upload.</p>

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

                                {{-- Selected-image preview strip (filled by JS) --}}
                                <div id="modal-preview" class="media-preview"></div>
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
    {{-- Data island (non-executable JSON) read by public/js/listing-form.js --}}
    <script type="application/json" id="existing-images">@json($existingImages)</script>
    <script type="application/json" id="form-errors">@json($errors->getMessages())</script>

    @unless ($mapsKey)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endunless
    <script src="{{ asset('js/listing-form.js') }}"></script>
    @if ($mapsKey)
        <script async src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=initListingMap"></script>
    @endif
@endpush
