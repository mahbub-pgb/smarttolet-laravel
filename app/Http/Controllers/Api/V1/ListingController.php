<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\ReportListingRequest;
use App\Http\Requests\Listing\SearchListingRequest;
use App\Http\Requests\Listing\StoreListingRequest;
use App\Http\Requests\Listing\UpdateListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Services\Geo\GeoService;
use App\Services\Listing\ListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function __construct(
        private ListingService $listings,
        private GeoService $geo,
    ) {}

    /**
     * GET /listings — public search/browse (cacheable).
     *
     * @OA\Get(
     *     path="/listings",
     *     tags={"Listings"},
     *     summary="Search and browse approved listings",
     *     @OA\Parameter(name="q", in="query", description="Keyword (fulltext)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="area", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="min_rent", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="max_rent", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="bedrooms", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="lat", in="query", description="Latitude for radius search", @OA\Schema(type="number")),
     *     @OA\Parameter(name="lng", in="query", description="Longitude for radius search", @OA\Schema(type="number")),
     *     @OA\Parameter(name="radius", in="query", description="Radius in km", @OA\Schema(type="number")),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", enum={"nearest","price_asc","price_desc","oldest","popular"})),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated listings", @OA\JsonContent(ref="#/components/schemas/ApiSuccess"))
     * )
     */
    public function index(SearchListingRequest $request): JsonResponse
    {
        $paginator = $this->listings->search(
            $request->filters(),
            $request->perPage(),
            $request->pageNumber(),
        );

        return $this->paginatedResponse(
            $paginator,
            'OK',
            fn ($items) => ListingResource::collection($items),
        )->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
    }

    /** GET /listings/{idOrSlug} — detail; increments view_count. */
    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $listing = $this->listings->showByIdOrSlug(
            $idOrSlug,
            $request->user(),
            $this->fingerprint($request),
        );

        return $this->ok(new ListingResource($listing), 'OK');
    }

    /** GET /listings/{idOrSlug}/nearby — nearby places via Google. */
    public function nearby(Request $request, string $idOrSlug): JsonResponse
    {
        $listing = $this->listings->showByIdOrSlug($idOrSlug, $request->user(), $this->fingerprint($request));

        if (! $listing->hasLocation()) {
            return $this->ok([], 'Listing has no location set.');
        }

        $places = $this->geo->nearbyPlaces(
            (float) $listing->latitude,
            (float) $listing->longitude,
            $request->string('type')->value() ?: null,
        );

        return $this->ok($places, 'OK');
    }

    /** POST /listings/{id}/contact — reveal + track contact view. */
    public function contact(Request $request, Listing $listing): JsonResponse
    {
        $count = $this->listings->recordContactView($listing, $request->user(), $this->fingerprint($request));

        return $this->ok([
            'mobile' => $listing->owner->mobile,
            'name' => $listing->owner->name,
            'contact_view_count' => $count,
        ], 'OK');
    }

    /** POST /listings */
    public function store(StoreListingRequest $request): JsonResponse
    {
        $listing = $this->listings->create(
            $request->user(),
            $request->safe()->except('images'),
            $request->file('images', []),
        );

        return $this->created(new ListingResource($listing), 'Listing submitted.');
    }

    /** GET /listings/me/list */
    public function mine(Request $request): JsonResponse
    {
        $paginator = $this->listings->myListings(
            $request->user(),
            (int) $request->integer('limit', 15),
            (int) $request->integer('page', 1),
        );

        return $this->paginatedResponse($paginator, 'OK', fn ($items) => ListingResource::collection($items));
    }

    /** PUT /listings/{listing} */
    public function update(UpdateListingRequest $request, Listing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        $updated = $this->listings->update(
            $listing,
            $request->safe()->except('images'),
            $request->file('images', []),
        );

        return $this->ok(new ListingResource($updated), 'Listing updated.');
    }

    /** DELETE /listings/{listing} */
    public function destroy(Listing $listing): JsonResponse
    {
        $this->authorize('delete', $listing);

        $this->listings->delete($listing);

        return $this->noContentResponse('Listing deleted.');
    }

    /** POST /listings/{listing}/renew */
    public function renew(Listing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        return $this->ok(new ListingResource($this->listings->renew($listing)), 'Listing renewed.');
    }

    /** PATCH /listings/{listing}/status — mark rented/available. */
    public function setStatus(Request $request, Listing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        $rented = $request->boolean('rented');

        return $this->ok(new ListingResource($this->listings->setRented($listing, $rented)),
            $rented ? 'Marked as rented.' : 'Marked as available.');
    }

    /** DELETE /listings/bulk — bulk delete own listings. */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])['ids'];

        $deleted = $this->listings->bulkDelete($request->user(), $ids);

        return $this->ok(['deleted' => $deleted], 'Listings deleted.');
    }

    /** POST /listings/{listing}/report */
    public function report(ReportListingRequest $request, Listing $listing): JsonResponse
    {
        $report = $this->listings->report($request->user(), $listing, $request->validated());

        return $this->created(['id' => $report->id, 'status' => $report->status], 'Report submitted.');
    }

    /** Stable per-viewer fingerprint for guest dedupe (hashed ip + ua). */
    private function fingerprint(Request $request): string
    {
        return hash('sha256', $request->ip().'|'.$request->userAgent());
    }
}
