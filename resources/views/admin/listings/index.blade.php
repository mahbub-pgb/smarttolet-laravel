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
            <tr>
                <th>Listing</th>
                <th>Owner</th>
                <th>Rent</th>
                <th>Status</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($listings as $listing)
            <tr>
                <td>
                    <strong>{{ \Illuminate\Support\Str::limit($listing->title, 40) }}</strong><br>
                    <span class="muted">📍 {{ $listing->area_name }} · {{ ucfirst($listing->type) }}</span>
                    @if ($listing->status === \App\Models\Listing::STATUS_REJECTED && $listing->rejection_reason)
                        <br><span class="reject-inline" title="{{ $listing->rejection_reason }}">⛔ {{ \Illuminate\Support\Str::limit($listing->rejection_reason, 70) }}</span>
                    @endif
                </td>
                <td>{{ $listing->owner?->name ?? '—' }}<br><span class="muted">{{ $listing->owner?->mobile }}</span></td>
                <td>৳{{ number_format($listing->rent) }}</td>
                <td><span class="pill pill-{{ $listing->status }}">{{ ucfirst($listing->status) }}</span></td>
                <td>
                    <div class="row-actions">
                        @if ($listing->status === \App\Models\Listing::STATUS_APPROVED)
                        {{-- Approved listings open the live public page. --}}
                        <a href="{{ route('listings.show', $listing->slug) }}" target="_blank" class="btn btn-ghost btn-sm">View ↗</a>
                        @else
                        {{-- Non-approved listings preview in a modal for review. --}}
                        <button type="button" class="btn btn-ghost btn-sm preview-btn"
                            data-url="{{ route('admin.listings.preview', $listing) }}">Preview</button>
                        <form method="POST" action="{{ route('admin.listings.approve', $listing) }}">
                            @csrf
                            <button class="btn btn-sm" type="submit">Approve</button>
                        </form>
                        @endif

                        @if ($listing->status !== \App\Models\Listing::STATUS_REJECTED)
                        <button type="button" class="btn btn-ghost btn-sm reject-btn"
                            data-url="{{ route('admin.listings.reject', $listing) }}"
                            data-title="{{ $listing->title }}">Reject</button>
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
            <tr>
                <td colspan="5" class="muted">No listings found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="pagination-wrap">{{ $listings->links() }}</div>

{{-- ===== Preview modal ===== --}}
<div class="modal-overlay" id="preview-modal" aria-hidden="true">
    <div class="modal modal-lg" role="dialog" aria-modal="true" aria-label="Listing preview">
        <div class="modal-head">
            <h3>Listing preview</h3>
            <button type="button" class="modal-close" id="preview-close" aria-label="Close">✕</button>
        </div>
        <div class="modal-body" id="preview-body">
            <p class="muted">Loading…</p>
        </div>
    </div>
</div>

{{-- ===== Reject (with message) modal ===== --}}
<div class="modal-overlay" id="reject-modal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Reject listing">
        <div class="modal-head">
            <h3>Reject listing</h3>
            <button type="button" class="modal-close" id="reject-close" aria-label="Close">✕</button>
        </div>
        <form method="POST" id="reject-form">
            @csrf
            <div class="modal-body">
                <p class="form-hint" id="reject-target">Tell the owner why this listing was rejected. They will see this message.</p>
                <textarea name="reason" rows="14" maxlength="1000" required
                    placeholder="e.g. The photos don't match the description. Please re-upload clear photos of the actual property."></textarea>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" id="reject-cancel">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject &amp; notify owner</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin-listings.js') }}"></script>
@endpush
