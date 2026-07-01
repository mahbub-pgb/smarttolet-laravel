@php($img = $listing->images[0]['url'] ?? null)
<a href="{{ route('listings.show', $listing->slug) }}" class="card">
    <div class="card-media">
        @if ($img)<img src="{{ $img }}" alt="{{ $listing->title }}" loading="lazy">@endif
        <span class="badge">{{ $listing->type }}</span>
        @auth('web')
            <button type="button"
                class="fav-btn {{ in_array($listing->id, $favoriteIds ?? []) ? 'is-fav' : '' }}"
                data-url="{{ route('favorites.toggle', $listing) }}"
                aria-label="Save to favourites" aria-pressed="{{ in_array($listing->id, $favoriteIds ?? []) ? 'true' : 'false' }}">♥</button>
        @else
            <a href="{{ route('login') }}" class="fav-btn" aria-label="Log in to save" title="Log in to save">♥</a>
        @endauth
    </div>
    <div class="card-body">
        <div class="card-price">৳{{ number_format($listing->rent) }} <small>/ month</small></div>
        <h3 class="card-title">{{ \Illuminate\Support\Str::limit($listing->title, 52) }}</h3>
        <div class="card-meta">
            <span>📍 {{ $listing->area_name }}</span>
            @if ($listing->bedrooms)<span>🛏 {{ $listing->bedrooms }} bed</span>@endif
            @if ($listing->bathrooms)<span>🛁 {{ $listing->bathrooms }} bath</span>@endif
        </div>
    </div>
</a>
