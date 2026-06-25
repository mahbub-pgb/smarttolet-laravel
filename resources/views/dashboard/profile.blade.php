@extends('layouts.app')

@section('title', 'Profile Settings')

@php
    $mapsKey = config('geo.google.browser_key');
    $lat = old('latitude', $user->latitude);
    $lng = old('longitude', $user->longitude);
@endphp

@section('content')
    <section class="section">
        <div class="container container-narrow">
            <div class="section-head">
                <div>
                    <h2>Account</h2>
                    <p>Manage your profile and preferences.</p>
                </div>
            </div>

            @include('partials.dashboard-tabs')

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul style="margin:0 0 0 18px">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" class="listing-form">
                @csrf
                @method('PUT')

                <fieldset class="form-card">
                    <legend>Profile</legend>

                    <div class="avatar-row">
                        <div class="avatar">
                            @if ($user->photo)<img src="{{ $user->photo }}" alt="">@else<span>{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>@endif
                        </div>
                        <label>Profile photo
                            <input type="file" name="photo" accept="image/*">
                        </label>
                    </div>

                    <div class="form-grid-2">
                        <label>Name *<input type="text" name="name" required value="{{ old('name', $user->name) }}"></label>
                        <label>Email<input type="email" name="email" value="{{ old('email', $user->email) }}"></label>
                    </div>

                    <div class="form-row">
                        <label>Mobile
                            <input type="text" value="{{ $user->mobile }}" disabled>
                        </label>
                    </div>

                    <div class="form-grid-3">
                        <label>Date of birth<input type="date" name="dob" value="{{ old('dob', optional($user->dob)->format('Y-m-d')) }}"></label>
                        <label>Gender
                            <select name="gender">
                                <option value="">—</option>
                                @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $v => $l)
                                    <option value="{{ $v }}" @selected(old('gender', $user->gender) === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Occupation<input type="text" name="occupation" value="{{ old('occupation', $user->occupation) }}"></label>
                    </div>

                    <div class="form-row">
                        <label>Preferred areas <small>(comma separated)</small>
                            <input type="text" name="area_preferences" value="{{ old('area_preferences', is_array($user->area_preferences) ? implode(', ', $user->area_preferences) : '') }}" placeholder="Dhanmondi, Banani, Gulshan">
                        </label>
                    </div>
                </fieldset>

                {{-- ===== Your location ===== --}}
                <fieldset class="form-card">
                    <legend>Your location</legend>
                    <p class="form-hint">Set your location once and new listings will start from here. Use your current location, search, or click the map to drop a pin.</p>

                    <div class="map-toolbar">
                        <input type="text" id="map-search" placeholder="🔍 Search for a place or address…" autocomplete="off">
                        <button type="button" class="btn btn-ghost btn-sm" id="use-location">📍 Use current location</button>
                    </div>

                    <div id="pick-map" class="pick-map" data-maps="{{ $mapsKey ? 'google' : 'leaflet' }}" data-lat="{{ $lat }}" data-lng="{{ $lng }}" data-zoom="{{ $mapDefaultZoom }}" data-zoom-pinned="{{ $mapPinnedZoom }}" data-default-lat="{{ $mapDefaultLat }}" data-default-lng="{{ $mapDefaultLng }}"></div>

                    <input type="hidden" name="latitude" id="latitude" value="{{ $lat }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ $lng }}">

                    <div class="form-row">
                        <label>Address
                            <input type="text" name="address" id="address" value="{{ old('address', $user->address) }}" placeholder="Auto-filled from the map">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="form-card">
                    <legend>Change password</legend>
                    <p class="form-hint">Leave blank to keep your current password.</p>
                    <div class="form-grid-2">
                        <label>New password<input type="password" name="password" autocomplete="new-password"></label>
                        <label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password"></label>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn">Save changes</button>
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
    @unless ($mapsKey)
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endunless
    <script src="{{ asset('js/listing-form.js') }}"></script>
    @if ($mapsKey)
        <script async src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=initListingMap"></script>
    @endif
@endpush
