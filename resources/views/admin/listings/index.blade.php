@extends('admin.layout')

@section('title', 'Manage Listings')
@section('heading', 'Manage Listings')

@section('content')
    @php($tabs = ['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'draft' => 'Draft', 'rejected' => 'Rejected', 'rented' => 'Rented'])

    <div class="filter-tabs">
        @foreach ($tabs as $key => $label)
            <a href="{{ route('admin.listings.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
               class="filter-tab {{ (string) $status === (string) $key ? 'active' : '' }}">
                {{ $label }}
                @if ($key !== '' && isset($counts[$key]))<span class="filter-count">{{ $counts[$key] }}</span>@endif
            </a>
        @endforeach
    </div>

    <form method="GET" class="admin-search">
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search title or area…">
        <button class="btn btn-sm" type="submit">Search</button>
    </form>

    <section class="panel">
        <table class="table">
            <thead>
                <tr><th>Listing</th><th>Owner</th><th>Rent</th><th>Status</th><th style="text-align:right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($listings as $listing)
                    <tr>
                        <td>
                            <strong>{{ \Illuminate\Support\Str::limit($listing->title, 40) }}</strong><br>
                            <span class="muted">📍 {{ $listing->area_name }} · {{ ucfirst($listing->type) }}</span>
                        </td>
                        <td>{{ $listing->owner?->name ?? '—' }}<br><span class="muted">{{ $listing->owner?->mobile }}</span></td>
                        <td>৳{{ number_format($listing->rent) }}</td>
                        <td><span class="pill pill-{{ $listing->status }}">{{ ucfirst($listing->status) }}</span></td>
                        <td>
                            <div class="row-actions">
                                @if ($listing->status === \App\Models\Listing::STATUS_APPROVED)
                                    <a href="{{ route('listings.show', $listing->slug) }}" target="_blank" class="btn btn-ghost btn-sm">View</a>
                                @endif

                                @if ($listing->status !== \App\Models\Listing::STATUS_APPROVED)
                                    <form method="POST" action="{{ route('admin.listings.approve', $listing) }}">
                                        @csrf
                                        <button class="btn btn-sm" type="submit">Approve</button>
                                    </form>
                                @endif

                                @if ($listing->status !== \App\Models\Listing::STATUS_DRAFT)
                                    <form method="POST" action="{{ route('admin.listings.draft', $listing) }}">
                                        @csrf
                                        <button class="btn btn-ghost btn-sm" type="submit">Draft</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.listings.destroy', $listing) }}"
                                      onsubmit="return confirm('Delete this listing permanently?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost btn-sm btn-danger" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No listings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="pagination">{{ $listings->links() }}</div>
@endsection
