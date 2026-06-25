@extends('admin.layout')

@section('title', 'Map Settings')
@section('heading', 'Map Settings')

@section('content')
    @php($browserKey = $settings['google_maps_browser_key'] ?? null)

    <div class="admin-cols">
        <section class="panel" style="max-width:560px">
            <h3>Map zoom &amp; key</h3>
            <p class="muted" style="margin-top:-6px">Controls how far in every map (listing form, profile, and the public listing page) opens. Higher numbers zoom in closer.</p>

            <form method="POST" action="{{ route('admin.settings.maps.update') }}">
                @csrf

                <div class="field">
                    <label>Default zoom <small class="muted">(no location set yet)</small></label>
                    <input type="number" name="map_default_zoom" min="0" max="22" step="1"
                           value="{{ old('map_default_zoom', $settings['map_default_zoom'] ?? 12) }}">
                    <p class="muted" style="font-size:0.8rem;margin:6px 0 0">City-wide view. 0 = whole world, ~12 = city, 22 = building.</p>
                    @error('map_default_zoom')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label>Pinned zoom <small class="muted">(a location is set / shown)</small></label>
                    <input type="number" name="map_pinned_zoom" min="0" max="22" step="1"
                           value="{{ old('map_pinned_zoom', $settings['map_pinned_zoom'] ?? 16) }}">
                    <p class="muted" style="font-size:0.8rem;margin:6px 0 0">Close-up view used once a pin is placed or a listing is opened.</p>
                    @error('map_pinned_zoom')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label>Default map centre <small class="muted">(latitude, longitude)</small></label>
                    <div style="display:flex;gap:10px">
                        <input type="number" name="map_default_lat" step="any" min="-90" max="90"
                               value="{{ old('map_default_lat', $settings['map_default_lat'] ?? 23.8103) }}" placeholder="Latitude">
                        <input type="number" name="map_default_lng" step="any" min="-180" max="180"
                               value="{{ old('map_default_lng', $settings['map_default_lng'] ?? 90.4125) }}" placeholder="Longitude">
                    </div>
                    <p class="muted" style="font-size:0.8rem;margin:6px 0 0">Where the browse map and empty location pickers open. Copy coordinates from Google Maps (right-click → the lat,lng at the top).</p>
                    @error('map_default_lat')<div class="field-error">{{ $message }}</div>@enderror
                    @error('map_default_lng')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label>Google Maps browser key <small class="muted">(optional)</small></label>
                    <input type="text" name="google_maps_browser_key"
                           value="{{ old('google_maps_browser_key', $browserKey) }}"
                           placeholder="Leave blank to use the OpenStreetMap fallback" autocomplete="off">
                    <p class="muted" style="font-size:0.8rem;margin:6px 0 0">Restrict this key by HTTP referrer in Google Cloud. Without it, maps fall back to Leaflet / OpenStreetMap.</p>
                    @error('google_maps_browser_key')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn">Save settings</button>
            </form>
        </section>

        <section class="panel" style="max-width:320px;align-self:start">
            <h3>Zoom guide</h3>
            <ul class="muted" style="line-height:1.9;padding-left:18px;margin:0">
                <li><strong>0–3</strong> — world / continent</li>
                <li><strong>4–6</strong> — country</li>
                <li><strong>10–12</strong> — city</li>
                <li><strong>14–16</strong> — streets</li>
                <li><strong>18–22</strong> — buildings</li>
            </ul>
        </section>
    </div>
@endsection
