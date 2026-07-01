@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <section class="section">
        <div class="container container-narrow">
            <div class="section-head">
                <div>
                    <h2>Notifications</h2>
                    <p>Search-alert matches and updates on your listings.</p>
                </div>
                @if ($notifications->getCollection()->contains(fn ($n) => ! $n->is_read))
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">Mark all read</button>
                    </form>
                @endif
            </div>

            @forelse ($notifications as $note)
                @php($p = $note->payload ?? [])
                @php($slug = $p['slug'] ?? null)
                @php($link = $slug ? route('listings.show', $slug) : ($p['listing_id'] ?? null ? url('/listings') : '#'))
                @php([$icon, $text] = match ($note->type) {
                    'listing_match' => ['🔔', 'New match for your saved search “'.($p['search_name'] ?? 'search').'”: '.($p['title'] ?? 'a listing')],
                    'listing_approved' => ['✅', 'Your listing “'.($p['title'] ?? '').'” was approved and is now live.'],
                    'listing_rejected' => ['⚠️', 'Your listing “'.($p['title'] ?? '').'” was not approved'.(!empty($p['reason']) ? ': '.$p['reason'] : '.')],
                    default => ['🔔', 'You have a new notification.'],
                })

                <a href="{{ $link }}" class="notif {{ $note->is_read ? '' : 'notif-unread' }}">
                    <span class="notif-icon">{{ $icon }}</span>
                    <span class="notif-body">
                        <span class="notif-text">{{ $text }}</span>
                        <span class="notif-time">{{ $note->created_at->diffForHumans() }}</span>
                    </span>
                    @unless ($note->is_read)<span class="notif-dot" aria-label="Unread"></span>@endunless
                </a>
            @empty
                <div class="empty">
                    <p>No notifications yet. Save a search on the listings page to get alerts when matching homes are posted.</p>
                    <a href="{{ route('listings.index') }}" class="btn btn-ghost btn-sm">Browse listings</a>
                </div>
            @endforelse

            <div class="pagination-wrap">{{ $notifications->links() }}</div>
        </div>
    </section>
@endsection
