@extends('layouts.app')

@section('title', 'Browse Listings')

@section('content')
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>All listings</h2>
                    <p>{{ $listings->total() }} {{ \Illuminate\Support\Str::plural('home', $listings->total()) }} available to rent.</p>
                </div>
                <a href="{{ route('listings.map', request()->only(['type', 'occupancy', 'q', 'area'])) }}" class="btn btn-ghost btn-sm">🗺 Map view</a>
            </div>

            @include('partials.category-nav', ['navRoute' => 'listings.index'])

            <div class="layout">
                <aside class="filters">
                    <form method="GET" action="{{ route('listings.index') }}">
                        <h3>Filter</h3>
                        <div class="field">
                            <label>Keyword</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Title, area…">
                        </div>
                        <div class="field">
                            <label>Area</label>
                            <select name="area" class="js-area-select" data-placeholder="Search or pick an area…">
                                <option value="">Any area</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area }}" @selected(request('area') === $area)>{{ $area }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Type</label>
                            <select name="type">
                                <option value="">Any type</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        @php($rentStep = 500)
                        @php($minRentVal = (int) request('min_rent', 0))
                        @php($maxRentVal = request()->filled('max_rent') ? (int) request('max_rent') : $rentCeiling)
                        <div class="field">
                            <label>Rent range</label>
                            <div class="rent-slider" data-min="0" data-max="{{ $rentCeiling }}" data-step="{{ $rentStep }}">
                                <span class="rent-slider-label">
                                    ৳<output id="rent-min-out"></output> – ৳<output id="rent-max-out"></output>
                                </span>
                                <div class="rent-slider-track">
                                    <div class="rent-slider-rail"></div>
                                    <div class="rent-slider-range" id="rent-range"></div>
                                    <input type="range" id="rent-min" min="0" max="{{ $rentCeiling }}"
                                           step="{{ $rentStep }}" value="{{ $minRentVal }}">
                                    <input type="range" id="rent-max" min="0" max="{{ $rentCeiling }}"
                                           step="{{ $rentStep }}" value="{{ $maxRentVal }}">
                                </div>
                                {{-- Actual submitted values (left blank at the extremes so we don't over-filter). --}}
                                <input type="hidden" name="min_rent" id="rent-min-input" value="{{ request('min_rent') }}">
                                <input type="hidden" name="max_rent" id="rent-max-input" value="{{ request('max_rent') }}">
                            </div>
                        </div>
                        <div class="field">
                            <label>Bedrooms</label>
                            <select name="bedrooms">
                                <option value="">Any</option>
                                @foreach ([1, 2, 3, 4, 5] as $b)
                                    <option value="{{ $b }}" @selected(request('bedrooms') == $b)>{{ $b }} {{ \Illuminate\Support\Str::plural('bed', $b) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>Sort by</label>
                            <select name="sort">
                                <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
                                <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                                <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                                <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-block">Apply filters</button>
                        <a href="{{ route('listings.index') }}" class="btn btn-ghost btn-block" style="margin-top:8px">Reset</a>
                    </form>
                </aside>

                <div>
                    @if ($listings->isEmpty())
                        <div class="empty">
                            <p>No listings match your search.</p>
                            <a href="{{ route('listings.index') }}" class="btn btn-ghost btn-sm">Clear filters</a>
                        </div>
                    @else
                        <div class="grid">
                            @foreach ($listings as $listing)
                                @include('partials.listing-card')
                            @endforeach
                        </div>
                        <div class="pagination-wrap">{{ $listings->links() }}</div>
                    @endif
                </div>
            </div>
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
