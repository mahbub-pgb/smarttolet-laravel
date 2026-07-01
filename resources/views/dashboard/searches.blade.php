@extends('layouts.app')

@section('title', 'Searches')

@section('content')
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Searches</h2>
                    <p>Build a search, run it, and get alerted when new matches are posted.</p>
                </div>
                <a href="{{ route('listings.index') }}" class="btn btn-sm">Browse listings</a>
            </div>

            @include('partials.dashboard-tabs')

            {{-- ===== Build a custom search ===== --}}
            <h3 style="margin:8px 0 14px">🔎 Create a search</h3>
            <p class="muted" style="margin:-6px 0 14px">Set your criteria, then run it to see matching listings — and get alerted when new ones are posted.</p>
            @php($areaGroups = config('bd_areas', []))
            <form method="POST" action="{{ route('dashboard.searches.store') }}" class="panel search-builder">
                @csrf
                <div class="search-builder-grid">
                    <label class="field">
                        <span>Area</span>
                        <select name="area" class="js-area-select" data-placeholder="Any area">
                            <option value="">Any area</option>
                            @foreach ($areaGroups as $city => $cityAreas)
                                <optgroup label="{{ $city }}">
                                    @foreach ($cityAreas as $area)
                                        <option value="{{ $area }}">{{ $area }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>Type</span>
                        <select name="type">
                            <option value="">Any type</option>
                            @foreach (\App\Models\Listing::TYPES as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field">
                        <span>Bedrooms</span>
                        <select name="bedrooms">
                            <option value="">Any</option>
                            @foreach ([1, 2, 3, 4, 5] as $b)
                                <option value="{{ $b }}">{{ $b }}+</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="field rent-field">
                        <span>Rent range</span>
                        @php($rentStep = 500)
                        <div class="rent-slider" data-min="0" data-max="{{ $rentCeiling }}" data-step="{{ $rentStep }}">
                            <span class="rent-slider-label">
                                ৳<output id="rent-min-out"></output> – ৳<output id="rent-max-out"></output>
                            </span>
                            <div class="rent-slider-track">
                                <div class="rent-slider-rail"></div>
                                <div class="rent-slider-range" id="rent-range"></div>
                                <input type="range" id="rent-min" min="0" max="{{ $rentCeiling }}" step="{{ $rentStep }}" value="0">
                                <input type="range" id="rent-max" min="0" max="{{ $rentCeiling }}" step="{{ $rentStep }}" value="{{ $rentCeiling }}">
                            </div>
                            <input type="hidden" name="min_rent" id="rent-min-input" value="">
                            <input type="hidden" name="max_rent" id="rent-max-input" value="">
                        </div>
                    </div>
                </div>
                <div class="search-builder-foot">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="notify" value="1" checked> 🔔 Alert me when new matches are posted
                    </label>
                    <button type="submit" class="btn">Save &amp; run search</button>
                </div>
            </form>

            {{-- ===== Saved searches ===== --}}
            <h3 style="margin:30px 0 14px">🔔 Your searches</h3>
            @if ($searches->isEmpty())
                <div class="empty">
                    <p>No saved searches yet — build one above to see matching homes and get alerts.</p>
                </div>
            @else
                <div class="saved-search-list">
                    @foreach ($searches as $search)
                        <div class="saved-search">
                            <div class="saved-search-info">
                                <strong>{{ $search->name }}</strong>
                                <span class="muted">
                                    {{ $search->notify ? '🔔 Alerts on' : '🔕 Alerts off' }}
                                    · saved {{ $search->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <div class="saved-search-actions">
                                <a href="{{ route('listings.index', $search->params ?? []) }}" class="btn btn-ghost btn-sm">Run search</a>
                                <form method="POST" action="{{ route('dashboard.searches.destroy', $search->id) }}"
                                      data-confirm="Remove the saved search “{{ $search->name }}”?"
                                      data-confirm-title="Remove search?" data-confirm-action="Remove">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('js/area-select.js') }}"></script>
    <script src="{{ asset('js/rent-slider.js') }}"></script>
@endpush
