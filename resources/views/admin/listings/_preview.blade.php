@php($images = $listing->images ?? [])
@php($embed = $listing->youtubeEmbedUrl())

<div class="preview">
    <div class="preview-head">
        <div>
            <span class="pill pill-{{ $listing->status }}">{{ ucfirst($listing->status) }}</span>
            <h2>{{ $listing->title }}</h2>
            <p class="muted">📍 {{ $listing->address }}, {{ $listing->area_name }} · {{ ucfirst($listing->type) }}</p>
        </div>
        <div class="preview-price">৳{{ number_format($listing->rent) }}<small>/mo</small></div>
    </div>

    @if ($listing->rejections->isNotEmpty())
        <div class="alert alert-error" style="margin-bottom:16px">
            <strong>Rejection history ({{ $listing->rejections->count() }})</strong>
            <ul class="reject-history">
                @foreach ($listing->rejections as $rejection)
                    <li>
                        <span class="reject-when">{{ $rejection->created_at->format('d M Y, h:i A') }}@if ($rejection->moderator) · {{ $rejection->moderator->name }}@endif</span>
                        {{ $rejection->reason }}
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif ($listing->rejection_reason)
        <div class="alert alert-error" style="margin-bottom:16px"><strong>Rejected:</strong> {{ $listing->rejection_reason }}</div>
    @endif

    @if (count($images))
        <div class="preview-gallery">
            @foreach ($images as $img)
                <a href="{{ $img['url'] }}" target="_blank" rel="noopener"><img src="{{ $img['url'] }}" alt=""></a>
            @endforeach
        </div>
    @else
        <p class="muted">No photos uploaded.</p>
    @endif

    <div class="preview-facts">
        <div><span>Owner</span><strong>{{ $listing->owner?->name ?? '—' }}</strong></div>
        <div><span>Contact</span><strong>{{ $listing->owner?->mobile ?? '—' }}</strong></div>
        @if ($listing->advance_amount)<div><span>Advance</span><strong>৳{{ number_format($listing->advance_amount) }}</strong></div>@endif
        @if ($listing->available_from)<div><span>Available</span><strong>{{ $listing->available_from->format('d M Y') }}</strong></div>@endif
        @if ($listing->bedrooms)<div><span>Bedrooms</span><strong>{{ $listing->bedrooms }}</strong></div>@endif
        @if ($listing->bathrooms)<div><span>Bathrooms</span><strong>{{ $listing->bathrooms }}</strong></div>@endif
        @if ($listing->area_sqft)<div><span>Area</span><strong>{{ number_format($listing->area_sqft) }} sq ft</strong></div>@endif
        @if ($listing->balconies)<div><span>Balconies</span><strong>{{ $listing->balconies }}</strong></div>@endif
        @if ($listing->floor_number !== null)<div><span>Floor</span><strong>{{ $listing->floor_number }}@if ($listing->building_floors) / {{ $listing->building_floors }}@endif</strong></div>@endif
    </div>

    <h4>Description</h4>
    <div class="prose">{!! nl2br(e($listing->description)) !!}</div>

    @if (!empty($listing->amenities))
        <h4>Amenities</h4>
        <div class="amenities">
            @foreach ($listing->amenities as $a)<span>{{ \App\Models\Listing::AMENITIES[$a] ?? ucfirst(str_replace('_', ' ', $a)) }}</span>@endforeach
        </div>
    @endif

    @if (!empty($listing->occupancy_rules))
        <h4>Occupancy &amp; Rules</h4>
        <div class="amenities">
            @foreach ($listing->occupancy_rules as $r)<span>{{ \App\Models\Listing::OCCUPANCY_RULES[$r] ?? ucfirst(str_replace('_', ' ', $r)) }}</span>@endforeach
        </div>
    @endif

    @if ($embed)
        <h4>Video tour</h4>
        <div class="video-embed"><iframe src="{{ $embed }}" title="Video tour" allowfullscreen></iframe></div>
    @endif

    @if ($listing->hasLocation())
        <p style="margin-top:14px">
            <a href="https://www.google.com/maps/search/?api=1&query={{ $listing->latitude }},{{ $listing->longitude }}"
               target="_blank" rel="noopener" class="btn btn-ghost btn-sm">📍 Open location in Google Maps</a>
        </p>
    @endif

    {{-- Moderation actions, available right from the preview --}}
    <div class="preview-actions">
        @if ($listing->status !== \App\Models\Listing::STATUS_APPROVED)
            <form method="POST" action="{{ route('admin.listings.approve', $listing) }}">@csrf<button class="btn" type="submit">✓ Approve &amp; publish</button></form>
        @else
            <a href="{{ route('listings.show', $listing->slug) }}" target="_blank" rel="noopener" class="btn">View live listing ↗</a>
        @endif
        @if ($listing->status !== \App\Models\Listing::STATUS_REJECTED)
            <button type="button" class="btn btn-ghost reject-btn"
                    data-url="{{ route('admin.listings.reject', $listing) }}"
                    data-title="{{ $listing->title }}">Reject…</button>
        @endif
        @if ($listing->status !== \App\Models\Listing::STATUS_DRAFT)
            <form method="POST" action="{{ route('admin.listings.draft', $listing) }}">@csrf<button class="btn btn-ghost" type="submit">Move to draft</button></form>
        @endif
        <form method="POST" action="{{ route('admin.listings.destroy', $listing) }}" onsubmit="return confirm('Delete this listing permanently?')">
            @csrf @method('DELETE')
            <button class="btn btn-ghost btn-danger" type="submit">Delete</button>
        </form>
    </div>
</div>
