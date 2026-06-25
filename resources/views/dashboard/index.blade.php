@extends('layouts.app')

@section('title', 'My Listings')

@section('content')
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Hi, {{ $user->name ?? 'there' }} 👋</h2>
                    <p>{{ $user->mobile }}@if ($user->email) · {{ $user->email }}@endif</p>
                </div>
                <a href="{{ route('dashboard.listings.create') }}" class="btn btn-sm">+ Add listing</a>
            </div>

            @include('partials.dashboard-tabs')

            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            @if ($listings->isEmpty())
                <div class="empty">
                    <p>You haven't posted any listings yet.</p>
                    <a href="{{ route('dashboard.listings.create') }}" class="btn btn-sm">Post your first listing</a>
                </div>
            @else
                <div class="grid">
                    @foreach ($listings as $listing)
                        @php($img = $listing->images[0]['url'] ?? null)
                        <div class="card">
                            <div class="card-media">
                                @if ($img)<img src="{{ $img }}" alt="">@endif
                                <span class="badge badge-{{ $listing->status }}">{{ ucfirst($listing->status) }}</span>
                            </div>
                            <div class="card-body">
                                <div class="card-price">৳{{ number_format($listing->rent) }} <small>/ month</small></div>
                                <h3 class="card-title">{{ \Illuminate\Support\Str::limit($listing->title, 52) }}</h3>
                                <div class="card-meta">
                                    <span>📍 {{ $listing->area_name }}</span>
                                    <span>👁 {{ $listing->view_count }}</span>
                                </div>

                                @if ($listing->status === \App\Models\Listing::STATUS_REJECTED && $listing->rejection_reason)
                                    <p class="card-note">Rejected: {{ $listing->rejection_reason }}</p>
                                @endif

                                <div class="card-actions">
                                    @if ($listing->status === \App\Models\Listing::STATUS_APPROVED)
                                        <a href="{{ route('listings.show', $listing->slug) }}" class="btn btn-ghost btn-sm">View</a>
                                    @endif
                                    <a href="{{ route('dashboard.listings.edit', $listing) }}" class="btn btn-ghost btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('dashboard.listings.destroy', $listing) }}"
                                          onsubmit="return confirm('Delete this listing? This cannot be undone.')" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="pagination-wrap">{{ $listings->links() }}</div>
            @endif
        </div>
    </section>
@endsection
