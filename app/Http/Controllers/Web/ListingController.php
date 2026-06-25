<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\Listing\ListingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function __construct(private ListingService $listings) {}

    /** GET /listings — browse / search all approved listings. */
    public function index(Request $request): View
    {
        $page = max(1, $request->integer('page', 1));

        $listings = $this->listings
            ->search($this->filters($request), 12, $page)
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

    /** GET /map — geocoded listings plotted on a map, optionally near the user. */
    public function map(Request $request): View
    {
        // Reuse the public search (keyword, category, geo radius, sorting) and
        // keep only the geocoded rows the map can actually plot.
        $listings = $this->listings
            ->search($this->filters($request), 500, 1)
            ->getCollection()
            ->filter(fn (Listing $l) => $l->latitude !== null && $l->longitude !== null)
            ->values();

        $types = Listing::query()->publiclyVisible()->distinct()->orderBy('type')->pluck('type');

        // Upper bound for the rent range slider, rounded up to a clean step.
        $rentCeiling = (int) Listing::query()->publiclyVisible()->max('rent');
        $rentCeiling = max((int) (ceil($rentCeiling / 1000) * 1000), 1000);

        // The pin the "Near me" search was centred on (so the JS can mark it).
        $origin = $request->filled('lat') && $request->filled('lng')
            ? ['lat' => (float) $request->input('lat'), 'lng' => (float) $request->input('lng')]
            : null;

        $points = $listings->map(fn (Listing $l) => [
            'id' => $l->id,
            'slug' => $l->slug,
            'title' => $l->title,
            'rent' => $l->rent,
            'area' => $l->area_name,
            'type' => $l->type,
            'bedrooms' => $l->bedrooms,
            'bathrooms' => $l->bathrooms,
            'area_sqft' => $l->area_sqft,
            'url' => route('listings.show', $l->slug),
            'lat' => (float) $l->latitude,
            'lng' => (float) $l->longitude,
            'image' => $l->images[0]['url'] ?? null,
        ])->values();

        return view('listings.map', compact('listings', 'points', 'types', 'origin', 'rentCeiling'));
    }

    /**
     * Build the public search filter array shared by the list and map views
     * from the request's query parameters.
     *
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        $filters = ['_scope' => 'public'];

        foreach (['q', 'area', 'type', 'occupancy', 'category', 'sort'] as $key) {
            if ($request->filled($key)) {
                $filters[$key] = (string) $request->input($key);
            }
        }

        foreach (['min_rent', 'max_rent', 'bedrooms', 'bathrooms'] as $key) {
            if ($request->filled($key)) {
                $filters[$key] = $request->integer($key);
            }
        }

        // Geo radius search (set by the map's "Near me" control).
        if ($request->filled('lat') && $request->filled('lng')) {
            $filters['lat'] = (float) $request->input('lat');
            $filters['lng'] = (float) $request->input('lng');
            if ($request->filled('radius')) {
                $filters['radius'] = (float) $request->input('radius');
            }
        }

        return $filters;
    }
}
