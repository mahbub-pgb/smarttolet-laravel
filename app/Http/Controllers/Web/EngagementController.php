<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\Engagement\FavoriteService;
use App\Services\Engagement\SavedSearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Session-authenticated favourites + saved searches for the Blade UI. Reuses
 * the same Engagement services as the REST API so behaviour stays consistent.
 */
class EngagementController extends Controller
{
    /** Filter keys that make up a saved search (mirrors ListingController::filters). */
    private const SEARCH_KEYS = [
        'q', 'area', 'type', 'occupancy', 'category', 'sort',
        'min_rent', 'max_rent', 'bedrooms', 'bathrooms', 'lat', 'lng', 'radius',
    ];

    public function __construct(
        private FavoriteService $favorites,
        private SavedSearchService $savedSearches,
    ) {}

    /** POST /listings/{listing}/favorite — toggle, returns the new state (AJAX). */
    public function toggleFavorite(Request $request, Listing $listing): JsonResponse
    {
        $user = $request->user();

        if (in_array($listing->id, $this->favorites->ids($user), true)) {
            $this->favorites->remove($user, $listing->id);
            $favorited = false;
        } else {
            $this->favorites->add($user, $listing->id);
            $favorited = true;
        }

        return response()->json(['favorited' => $favorited]);
    }

    /** GET /dashboard/saved — the "Saved" tab: favourited listings. */
    public function saved(Request $request): View
    {
        $user = $request->user();

        $favorites = $this->favorites
            ->listListings($user, 12, max(1, $request->integer('page', 1)))
            ->withQueryString();

        return view('dashboard.saved', compact('user', 'favorites'));
    }

    /** GET /dashboard/searches — the "Searches" tab: build + manage saved searches. */
    public function searches(Request $request): View
    {
        $user = $request->user();

        $searches = $this->savedSearches->list($user);

        // Upper bound for the rent slider in the "create a search" form.
        $rentCeiling = max((int) (ceil(((int) Listing::query()->publiclyVisible()->max('rent')) / 1000) * 1000), 1000);

        return view('dashboard.searches', compact('user', 'searches', 'rentCeiling'));
    }

    /**
     * POST /dashboard/saved-searches — save a custom query, then run it on the
     * public listings page so the user immediately sees the matches.
     */
    public function storeSearch(Request $request): RedirectResponse
    {
        $params = $this->searchParams($request);

        if ($params === []) {
            return back()->with('status', 'Add at least one filter before saving a search.');
        }

        $name = $request->filled('name')
            ? trim((string) $request->input('name'))
            : $this->searchName($params);

        $this->savedSearches->create($request->user(), $name, $params, notify: $request->boolean('notify'));

        // "Run" the query — land on the front listings page with these filters.
        return redirect()->route('listings.index', $params)
            ->with('status', "Saved “{$name}” — showing matching listings.");
    }

    /** DELETE /dashboard/saved-searches/{search} */
    public function destroySearch(Request $request, int $search): RedirectResponse
    {
        $this->savedSearches->delete($request->user(), $search);

        return back()->with('status', 'Saved search removed.');
    }

    /**
     * Pull the active filter parameters out of the request.
     *
     * @return array<string, mixed>
     */
    private function searchParams(Request $request): array
    {
        $params = [];

        foreach (self::SEARCH_KEYS as $key) {
            if ($request->filled($key)) {
                $params[$key] = $request->input($key);
            }
        }

        return $params;
    }

    /**
     * Build a readable name from the filters, e.g. "2-bed Apartment in Gulshan
     * under ৳15,000".
     *
     * @param  array<string, mixed>  $params
     */
    private function searchName(array $params): string
    {
        $parts = [];

        if (! empty($params['bedrooms'])) {
            $parts[] = $params['bedrooms'].'-bed';
        }
        $parts[] = ! empty($params['type']) ? ucfirst((string) $params['type']) : 'Listings';
        if (! empty($params['area'])) {
            $parts[] = 'in '.$params['area'];
        }
        if (! empty($params['max_rent'])) {
            $parts[] = 'under ৳'.number_format((int) $params['max_rent']);
        } elseif (! empty($params['min_rent'])) {
            $parts[] = 'over ৳'.number_format((int) $params['min_rent']);
        }
        if (! empty($params['q'])) {
            $parts[] = '“'.$params['q'].'”';
        }

        return trim(implode(' ', $parts)) ?: 'Saved search';
    }
}
