<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\Listing\ListingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function __construct(private ListingService $listings) {}

    /** GET /admin/listings — manage every user's listings. */
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();

        $listings = Listing::query()
            ->with('owner:id,name,mobile')
            ->when($status && in_array($status, ['draft', 'pending', 'approved', 'rejected', 'rented'], true),
                fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = (string) $request->string('q');
                $q->where(fn ($w) => $w->where('title', 'like', "%{$term}%")
                    ->orWhere('area_name', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = Listing::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.listings.index', compact('listings', 'counts', 'status'));
    }

    /** POST /admin/listings/{listing}/approve */
    public function approve(Listing $listing): RedirectResponse
    {
        $this->listings->moderate($listing, 'approve');

        return back()->with('status', "“{$listing->title}” approved and published.");
    }

    /** POST /admin/listings/{listing}/draft — unpublish back to draft. */
    public function draft(Listing $listing): RedirectResponse
    {
        $this->listings->markDraft($listing);

        return back()->with('status', "“{$listing->title}” moved to draft.");
    }

    /** DELETE /admin/listings/{listing} */
    public function destroy(Listing $listing): RedirectResponse
    {
        $title = $listing->title;
        $this->listings->delete($listing);

        return back()->with('status', "“{$title}” deleted.");
    }
}
