<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreListingRequest;
use App\Http\Requests\Web\UpdateListingRequest;
use App\Models\Listing;
use App\Models\Media;
use App\Services\Listing\ListingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardListingController extends Controller
{
    public function __construct(private ListingService $listings) {}

    /** GET /dashboard — "My Listings" tab. */
    public function index(Request $request): View
    {
        $user = $request->user();
        $listings = $user->listings()->latest()->paginate(10);

        return view('dashboard.index', compact('user', 'listings'));
    }

    /** GET /dashboard/analytics — views + phone reveals across the user's listings. */
    public function analytics(Request $request): View
    {
        $user = $request->user();

        $listings = $user->listings()
            ->withCount('contactViews')
            // Registered users who revealed the number (name + photo for the list).
            ->with(['contactViews' => fn ($q) => $q->whereNotNull('viewer_id')
                ->latest()
                ->with('viewer:id,name,photo')])
            ->latest()
            ->get();

        $totalViews = (int) $listings->sum('view_count');
        $totalContacts = (int) $listings->sum('contact_views_count');

        return view('dashboard.analytics', compact('user', 'listings', 'totalViews', 'totalContacts'));
    }

    /** GET /dashboard/listings/create — blank listing form. */
    public function create(Request $request): View
    {
        return view('dashboard.form', [
            'user' => $request->user(),
            'listing' => new Listing,
            'isEdit' => false,
            'mediaLibrary' => $this->mediaLibrary($request),
        ]);
    }

    /** POST /dashboard/listings — create a listing (draft or pending). */
    public function store(StoreListingRequest $request): RedirectResponse
    {
        try {
            $listing = $this->listings->create(
                $request->user(),
                $request->validated(),
                $request->file('images', []),
            );
        } catch (ApiException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()->route('dashboard')->with('status', $this->savedMessage($listing));
    }

    /** GET /dashboard/listings/{listing}/edit — edit form. */
    public function edit(Request $request, Listing $listing): View
    {
        $this->authorizeOwner($request, $listing);

        $listing->loadMissing('rejections');

        return view('dashboard.form', [
            'user' => $request->user(),
            'listing' => $listing,
            'isEdit' => true,
            'mediaLibrary' => $this->mediaLibrary($request),
        ]);
    }

    /** PUT /dashboard/listings/{listing} — update a listing. */
    public function update(UpdateListingRequest $request, Listing $listing): RedirectResponse
    {
        $this->authorizeOwner($request, $listing);

        try {
            $listing = $this->listings->update(
                $listing,
                $request->validated(),
                $request->file('images', []),
            );
        } catch (ApiException $e) {
            return back()->withInput()->withErrors(['form' => $e->getMessage()]);
        }

        return redirect()->route('dashboard')->with('status', $this->savedMessage($listing));
    }

    /** DELETE /dashboard/listings/{listing} — delete a listing. */
    public function destroy(Request $request, Listing $listing): RedirectResponse
    {
        $this->authorizeOwner($request, $listing);

        $this->listings->delete($listing);

        return redirect()->route('dashboard')->with('status', 'Listing deleted.');
    }

    private function authorizeOwner(Request $request, Listing $listing): void
    {
        abort_unless($listing->isOwnedBy($request->user()), 403);
    }

    /**
     * The signed-in user's reusable media-library images for the picker.
     *
     * @return Collection<int, Media>
     */
    private function mediaLibrary(Request $request)
    {
        return Media::query()
            ->where('owner_id', $request->user()->id)
            ->where('type', 'image')
            ->latest()
            ->limit(40)
            ->get();
    }

    private function savedMessage(Listing $listing): string
    {
        return $listing->status === Listing::STATUS_DRAFT
            ? 'Draft saved. Submit it for review when you are ready.'
            : 'Listing submitted for review. It will appear publicly once an admin approves it.';
    }
}
