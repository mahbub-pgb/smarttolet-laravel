@extends('layouts.app')

@section('title', 'Saved')

@section('content')
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Saved</h2>
                    <p>Your shortlisted homes.</p>
                </div>
                <a href="{{ route('listings.index') }}" class="btn btn-sm">Browse listings</a>
            </div>

            @include('partials.dashboard-tabs')

            {{-- ===== Favourited listings ===== --}}
            <h3 style="margin:8px 0 14px">❤️ Favourites</h3>
            @if ($favorites->isEmpty())
                <div class="empty">
                    <p>You haven't saved any listings yet. Tap the ❤️ on any listing to shortlist it here.</p>
                    <a href="{{ route('listings.index') }}" class="btn btn-ghost btn-sm">Browse listings</a>
                </div>
            @else
                <div class="grid">
                    @foreach ($favorites as $listing)
                        @include('partials.listing-card')
                    @endforeach
                </div>
                <div class="pagination-wrap">{{ $favorites->links() }}</div>
            @endif
        </div>
    </section>
@endsection
