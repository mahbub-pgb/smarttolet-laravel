@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Hi, {{ $user->name ?? 'there' }} 👋</h2>
                    <p>{{ $user->mobile }}@if ($user->email) · {{ $user->email }}@endif</p>
                </div>
                <a href="{{ route('listings.index') }}" class="btn btn-ghost btn-sm">Browse listings</a>
            </div>

            <div class="section-head" style="margin-top:8px">
                <div><h2 style="font-size:1.3rem">My listings</h2></div>
            </div>

            @if ($listings->isEmpty())
                <div class="empty">
                    <p>You haven't posted any listings yet.</p>
                </div>
            @else
                <div class="grid">
                    @foreach ($listings as $listing)
                        @php($img = $listing->images[0]['url'] ?? null)
                        <div class="card">
                            <div class="card-media">
                                @if ($img)<img src="{{ $img }}" alt="">@endif
                                <span class="badge">{{ $listing->status }}</span>
                            </div>
                            <div class="card-body">
                                <div class="card-price">৳{{ number_format($listing->rent) }} <small>/ month</small></div>
                                <h3 class="card-title">{{ \Illuminate\Support\Str::limit($listing->title, 52) }}</h3>
                                <div class="card-meta"><span>📍 {{ $listing->area_name }}</span><span>👁 {{ $listing->view_count }}</span></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="pagination">{{ $listings->links() }}</div>
            @endif
        </div>
    </section>
@endsection
