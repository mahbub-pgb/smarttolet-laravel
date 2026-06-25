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
                            <input type="text" name="area" value="{{ request('area') }}" placeholder="e.g. Dhanmondi">
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
                        <div class="field">
                            <label>Min rent</label>
                            <input type="number" name="min_rent" value="{{ request('min_rent') }}" placeholder="0">
                        </div>
                        <div class="field">
                            <label>Max rent</label>
                            <input type="number" name="max_rent" value="{{ request('max_rent') }}" placeholder="Any">
                        </div>
                        <div class="field">
                            <label>Min bedrooms</label>
                            <select name="bedrooms">
                                <option value="">Any</option>
                                @foreach ([1, 2, 3, 4] as $b)
                                    <option value="{{ $b }}" @selected(request('bedrooms') == $b)>{{ $b }}+</option>
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
