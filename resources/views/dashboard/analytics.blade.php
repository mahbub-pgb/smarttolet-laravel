@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>Analytics 📊</h2>
                    <p>Views and phone-number reveals across your listings.</p>
                </div>
                <a href="{{ route('dashboard.listings.create') }}" class="btn btn-sm">+ Add listing</a>
            </div>

            @include('partials.dashboard-tabs')

            {{-- ===== Summary ===== --}}
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-num">{{ number_format($listings->count()) }}</div>
                    <div class="stat-label">Listings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num">👁 {{ number_format($totalViews) }}</div>
                    <div class="stat-label">Total listing views</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num">📞 {{ number_format($totalContacts) }}</div>
                    <div class="stat-label">Phone number reveals</div>
                </div>
            </div>

            @if ($listings->isEmpty())
                <div class="empty">
                    <p>You haven't posted any listings yet.</p>
                    <a href="{{ route('dashboard.listings.create') }}" class="btn btn-sm">Post your first listing</a>
                </div>
            @else
                {{-- ===== Per-listing breakdown ===== --}}
                @foreach ($listings as $listing)
                    @php($revealers = $listing->contactViews->pluck('viewer')->filter())
                    <div class="analytics-row">
                        <div class="analytics-head">
                            <div>
                                <h3 class="card-title" style="margin:0">
                                    {{ \Illuminate\Support\Str::limit($listing->title, 60) }}
                                </h3>
                                <p style="margin:4px 0 0;color:var(--muted)">📍 {{ $listing->area_name }}</p>
                            </div>
                            <div class="analytics-stats">
                                <span title="Listing views">👁 {{ number_format($listing->view_count) }}</span>
                                <span title="Phone number reveals">📞 {{ number_format($listing->contact_views_count) }}</span>
                            </div>
                        </div>

                        <div class="analytics-revealers">
                            <p class="stat-label" style="margin:0 0 10px">
                                Who revealed the phone number
                                @if ($revealers->isNotEmpty())
                                    ({{ $revealers->count() }})
                                @endif
                            </p>

                            @if ($revealers->isEmpty())
                                <p style="margin:0;color:var(--muted)">No one has revealed the number yet.</p>
                            @else
                                <div class="revealer-list">
                                    @foreach ($revealers as $viewer)
                                        <div class="revealer" title="{{ $viewer->name }}">
                                            <div class="avatar avatar-sm">
                                                @if ($viewer->photo)
                                                    <img src="{{ $viewer->photo }}" alt="{{ $viewer->name }}">
                                                @else
                                                    <span>{{ strtoupper(substr($viewer->name ?? 'U', 0, 1)) }}</span>
                                                @endif
                                            </div>
                                            <span class="revealer-name">{{ $viewer->name ?? 'User' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </section>
@endsection
