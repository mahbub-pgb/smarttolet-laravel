@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <section class="hero">
        <div class="container">
            <h1>Find your next home, the smart way</h1>
            <p>Browse thousands of rooms, apartments, and offices for rent. Search by area, explore on the map, and connect directly with verified owners.</p>
            <form method="GET" action="{{ route('listings.index') }}" class="searchbar">
                <input type="text" name="q" placeholder="Search by title or keyword…">
                <input type="text" name="area" placeholder="Area / location">
                <select name="type">
                    <option value="">Any type</option>
                    <option value="apartment">Apartment</option>
                    <option value="room">Room</option>
                    <option value="sublet">Sublet</option>
                    <option value="office">Office</option>
                    <option value="shop">Shop</option>
                </select>
                <button type="submit" class="btn">Search</button>
            </form>
        </div>
    </section>

    @if ($areas->isNotEmpty())
        <section class="section" style="padding-bottom:0">
            <div class="container">
                <div class="section-head"><div><h2>Popular areas</h2><p>Jump straight to listings near you.</p></div></div>
                <div class="chips">
                    @foreach ($areas as $area)
                        <a href="{{ route('listings.index', ['area' => $area]) }}" class="chip">{{ $area }}</a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="container">
            <div class="section-head">
                <div><h2>Featured listings</h2><p>Freshly approved homes ready to rent.</p></div>
                <a href="{{ route('listings.index') }}" class="btn btn-ghost btn-sm">View all →</a>
            </div>
            @if ($featured->isEmpty())
                <div class="empty">No listings yet. Check back soon!</div>
            @else
                <div class="grid">
                    @foreach ($featured as $listing)
                        @include('partials.listing-card')
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($posts->isNotEmpty())
        <section class="section" style="background:#fff;border-top:1px solid var(--line)">
            <div class="container">
                <div class="section-head"><div><h2>From the blog</h2><p>Tips and guides for renters and owners.</p></div></div>
                <div class="grid">
                    @foreach ($posts as $post)
                        <div class="card">
                            <div class="card-media">@if ($post->cover_image)<img src="{{ $post->cover_image }}" alt="">@endif</div>
                            <div class="card-body">
                                <h3 class="card-title">{{ $post->title }}</h3>
                                <p style="color:var(--muted);margin:0">{{ \Illuminate\Support\Str::limit($post->excerpt, 110) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
