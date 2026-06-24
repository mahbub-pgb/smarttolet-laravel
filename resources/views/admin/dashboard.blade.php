@extends('admin.layout')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-num">{{ number_format($stats['users']) }}</div><div class="stat-label">Total users</div></div>
        <div class="stat-card"><div class="stat-num">{{ number_format($stats['listings']) }}</div><div class="stat-label">Listings</div></div>
        <div class="stat-card"><div class="stat-num">{{ number_format($stats['pending']) }}</div><div class="stat-label">Pending review</div></div>
        <div class="stat-card"><div class="stat-num">{{ number_format($stats['approved']) }}</div><div class="stat-label">Approved</div></div>
        <div class="stat-card"><div class="stat-num">{{ number_format($stats['reports']) }}</div><div class="stat-label">Open reports</div></div>
    </div>

    <div class="admin-cols">
        <section class="panel">
            <h3>Newest users</h3>
            <table class="table">
                <thead><tr><th>Name</th><th>Mobile</th><th>Role</th></tr></thead>
                <tbody>
                    @forelse ($recentUsers as $u)
                        <tr><td>{{ $u->name ?? '—' }}</td><td>{{ $u->mobile }}</td><td><span class="pill">{{ $u->role->label() }}</span></td></tr>
                    @empty
                        <tr><td colspan="3" class="muted">No users yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="panel">
            <h3>Newest listings</h3>
            <table class="table">
                <thead><tr><th>Title</th><th>Rent</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($recentListings as $l)
                        <tr><td>{{ \Illuminate\Support\Str::limit($l->title, 30) }}</td><td>৳{{ number_format($l->rent) }}</td><td><span class="pill">{{ $l->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="3" class="muted">No listings yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
