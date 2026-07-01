<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\Engagement\ContactRevealService;
use App\Services\Listing\ListingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ListingController extends Controller
{
    /** Upper bound on markers plotted on the map (clustered client-side). */
    private const MAP_MARKER_CAP = 10000;

    public function __construct(
        private ListingService $listings,
        private ContactRevealService $reveals,
    ) {}

    /** GET /listings — browse / search all approved listings. */
    public function index(Request $request): View
    {
        $page = max(1, $request->integer('page', 1));

        $listings = $this->listings
            ->search($this->filters($request), 12, $page)
            ->withQueryString();

        $types = Listing::query()->publiclyVisible()->distinct()->orderBy('type')->pluck('type');
        $areas = $this->areaOptions();
        $rentCeiling = $this->rentCeiling();

        return view('listings.index', compact('listings', 'types', 'areas', 'rentCeiling'));
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
        // This is a public route with no auth middleware, so the default guard
        // (JWT/api) is active — read the session (web) guard explicitly.
        $viewer = $request->user('web');
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

        // Whether this logged-in viewer has already unlocked the owner's number
        // (so we skip the "click to show number" glass on repeat visits).
        $contactRevealed = $viewer !== null && $this->reveals->hasRevealed($viewer, $listing->id);

        return view('listings.show', compact('listing', 'related', 'isPreview', 'contactRevealed'));
    }

    /** POST /listings/{listing}/reveal-contact — unlock & persist the owner's number for this user. */
    public function revealContact(Request $request, Listing $listing): JsonResponse
    {
        $listing->loadMissing('owner:id,mobile');

        $mobile = $this->reveals->reveal($request->user(), $listing);

        return response()->json(['mobile' => $mobile]);
    }

    /** GET /map — geocoded listings plotted on a map, optionally near the user. */
    public function map(Request $request): View
    {
        // Reuse the public search (keyword, category, sorting) and keep only the
        // geocoded rows the map can plot. The map clusters markers, so we plot
        // every match up to a generous safety cap (rather than the old 500).
        $listings = $this->listings
            ->search($this->filters($request), self::MAP_MARKER_CAP, 1)
            ->getCollection()
            ->filter(fn (Listing $l) => $l->latitude !== null && $l->longitude !== null)
            ->values();

        $types = Listing::query()->publiclyVisible()->distinct()->orderBy('type')->pluck('type');

        // Upper bound for the rent range slider, rounded up to a clean step.
        $rentCeiling = $this->rentCeiling();

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

        return view('listings.map', compact('listings', 'points', 'types', 'rentCeiling'));
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

        \Log::debug('Filters built from request: '.json_encode($filters));

        return $filters;
    }

    /**
     * Distinct area names across publicly visible listings, for the area
     * search datalist (type-to-filter / scroll-to-pick).
     *
     * @return Collection<int, string>
     */
    private function areaOptions(): Collection
    {
        return Listing::query()
            ->publiclyVisible()
            ->whereNotNull('area_name')
            ->where('area_name', '!=', '')
            ->select('area_name')
            ->distinct()
            ->orderBy('area_name')
            ->pluck('area_name');
    }

    /**
     * Upper bound for the rent range slider, rounded up to a clean step
     * (minimum 1000 so the slider always has a usable range).
     */
    private function rentCeiling(): int
    {
        $max = (int) Listing::query()->publiclyVisible()->max('rent');

        return max((int) (ceil($max / 1000) * 1000), 1000);
    }
}
