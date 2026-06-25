<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    /** GET /listings — browse / search all approved listings. */
    public function index(Request $request): View
    {
        $listings = Listing::query()
            ->publiclyVisible()
            ->with('owner:id,name,mobile,photo')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = (string) $request->string('q');
                $q->where(function ($w) use ($term) {
                    $w->where('title', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('area_name', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('area'), fn ($q) => $q->where('area_name', 'like', '%'.$request->string('area').'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('min_rent'), fn ($q) => $q->where('rent', '>=', $request->integer('min_rent')))
            ->when($request->filled('max_rent'), fn ($q) => $q->where('rent', '<=', $request->integer('max_rent')))
            ->when($request->filled('bedrooms'), fn ($q) => $q->where('bedrooms', '>=', $request->integer('bedrooms')))
            ->when($request->string('sort')->value() === 'price_asc', fn ($q) => $q->orderBy('rent'))
            ->when($request->string('sort')->value() === 'price_desc', fn ($q) => $q->orderByDesc('rent'))
            ->when($request->string('sort')->value() === 'oldest', fn ($q) => $q->oldest())
            ->when(! $request->filled('sort') || $request->string('sort')->value() === 'newest', fn ($q) => $q->latest())
            ->paginate(12)
            ->withQueryString();

        $types = Listing::query()->publiclyVisible()->distinct()->orderBy('type')->pluck('type');

        return view('listings.index', compact('listings', 'types'));
    }

    /** GET /listings/{slug} — single listing detail. */
    public function show(Request $request, string $slug): View
    {
        $listing = Listing::query()
            ->with('owner:id,name,mobile,photo')
            ->where('slug', $slug)
            ->firstOrFail();

        // Non-approved listings are previewable only by the owner or staff
        // (so admins can review a listing before approving or deleting it).
        $viewer = $request->user();
        $canPreview = $viewer && ($listing->isOwnedBy($viewer) || $viewer->isStaff());

        abort_unless($listing->isPubliclyVisible() || $canPreview, 404);

        $listing->increment('view_count');

        $related = Listing::query()
            ->publiclyVisible()
            ->where('id', '!=', $listing->id)
            ->where('area_name', $listing->area_name)
            ->latest()
            ->limit(3)
            ->get();

        $isPreview = ! $listing->isPubliclyVisible();

        return view('listings.show', compact('listing', 'related', 'isPreview'));
    }

    /** GET /map — all geocoded listings plotted on a map. */
    public function map(Request $request): View
    {
        $listings = Listing::query()
            ->publiclyVisible()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('owner:id,name')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->limit(500)
            ->get();

        $points = $listings->map(fn (Listing $l) => [
            'id' => $l->id,
            'slug' => $l->slug,
            'title' => $l->title,
            'rent' => $l->rent,
            'area' => $l->area_name,
            'type' => $l->type,
            'lat' => (float) $l->latitude,
            'lng' => (float) $l->longitude,
            'image' => $l->images[0]['url'] ?? null,
        ])->values();

        return view('listings.map', compact('listings', 'points'));
    }
}
