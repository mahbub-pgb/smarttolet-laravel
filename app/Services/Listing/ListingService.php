<?php

declare(strict_types=1);

namespace App\Services\Listing;

use App\Exceptions\ApiException;
use App\Models\ContactView;
use App\Models\Listing;
use App\Models\ListingVisit;
use App\Models\Report;
use App\Models\User;
use App\Repositories\Contracts\ListingRepositoryInterface;
use App\Services\Media\ImageService;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ListingService
{
    /** Days a listing stays live after approval before needing renewal. */
    private const LIFETIME_DAYS = 30;

    public function __construct(
        private ListingRepositoryInterface $listings,
        private ImageService $images,
        private NotificationService $notifications,
    ) {}

    // --- Read ------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Listing>
     */
    public function search(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->listings->search($filters, $perPage, $page);
    }

    public function showByIdOrSlug(string $idOrSlug, ?User $viewer, ?string $fingerprint): Listing
    {
        $listing = $this->listings->findByIdOrSlug($idOrSlug);

        if (! $listing) {
            throw ApiException::notFound('Listing not found.', 'listing_not_found');
        }

        // Non-owners may only view publicly-visible listings.
        $isOwnerOrStaff = $viewer && ($listing->isOwnedBy($viewer) || $viewer->isStaff());
        if (! $isOwnerOrStaff && $listing->status !== Listing::STATUS_APPROVED) {
            throw ApiException::notFound('Listing not found.', 'listing_not_found');
        }

        $this->recordVisit($listing, $viewer, $fingerprint);

        return $listing->loadMissing('owner:id,name,mobile,photo');
    }

    /**
     * @return LengthAwarePaginator<Listing>
     */
    public function myListings(User $user, int $perPage, int $page): LengthAwarePaginator
    {
        return Listing::query()
            ->ownedBy($user->id)
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    // --- Write -----------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    public function create(User $user, array $data, array $files = []): Listing
    {
        $this->assertWithinPlanLimit($user);

        $images = $files ? $this->images->uploadMany($files) : ($data['images'] ?? []);

        $asDraft = (bool) ($data['as_draft'] ?? false);

        $listing = $this->listings->create([
            'owner_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'rent' => $data['rent'],
            'bedrooms' => $data['bedrooms'] ?? 0,
            'bathrooms' => $data['bathrooms'] ?? 0,
            'area_name' => $data['area_name'],
            'address' => $data['address'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'amenities' => $data['amenities'] ?? [],
            'images' => $images,
            'status' => $asDraft ? Listing::STATUS_DRAFT : Listing::STATUS_PENDING,
        ]);

        $this->syncGeoPoint($listing);

        return $listing;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    public function update(Listing $listing, array $data, array $files = []): Listing
    {
        if ($files) {
            $newImages = $this->images->uploadMany($files);
            $data['images'] = array_merge($listing->images ?? [], $newImages);
        }

        // Editing a non-draft listing sends it back to moderation.
        if ($listing->status === Listing::STATUS_APPROVED && $this->touchesReviewableFields($data)) {
            $data['status'] = Listing::STATUS_PENDING;
        }

        $listing->fill($data)->save();
        $this->syncGeoPoint($listing);

        return $listing->refresh();
    }

    public function delete(Listing $listing): void
    {
        foreach ($listing->images ?? [] as $image) {
            $this->images->delete($image['public_id'] ?? null, $image['disk'] ?? 'cloudinary', $image['url'] ?? '');
        }

        $listing->delete();
    }

    /**
     * @param  array<int, int>  $ids
     */
    public function bulkDelete(User $user, array $ids): int
    {
        $listings = Listing::query()->ownedBy($user->id)->whereIn('id', $ids)->get();

        foreach ($listings as $listing) {
            $this->delete($listing);
        }

        return $listings->count();
    }

    public function renew(Listing $listing): Listing
    {
        $listing->forceFill([
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
            'status' => $listing->status === Listing::STATUS_RENTED ? Listing::STATUS_APPROVED : $listing->status,
        ])->save();

        return $listing->refresh();
    }

    public function setRented(Listing $listing, bool $rented): Listing
    {
        $listing->forceFill([
            'status' => $rented ? Listing::STATUS_RENTED : Listing::STATUS_APPROVED,
        ])->save();

        return $listing->refresh();
    }

    // --- Moderation ------------------------------------------------------

    public function moderate(Listing $listing, string $action, ?string $reason = null): Listing
    {
        if ($action === 'approve') {
            $listing->forceFill([
                'status' => Listing::STATUS_APPROVED,
                'approved_at' => now(),
                'expires_at' => now()->addDays(self::LIFETIME_DAYS),
                'rejection_reason' => null,
            ])->save();

            $this->notifications->notify($listing->owner_id, 'listing_approved', [
                'listing_id' => $listing->id,
                'title' => $listing->title,
            ]);
        } elseif ($action === 'reject') {
            $listing->forceFill([
                'status' => Listing::STATUS_REJECTED,
                'rejection_reason' => $reason,
            ])->save();

            $this->notifications->notify($listing->owner_id, 'listing_rejected', [
                'listing_id' => $listing->id,
                'title' => $listing->title,
                'reason' => $reason,
            ]);
        } else {
            throw ApiException::badRequest('Unknown moderation action.', 'invalid_action');
        }

        return $listing->refresh();
    }

    // --- Reports ---------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     */
    public function report(User $reporter, Listing $listing, array $data): Report
    {
        if ($reporter->id === $listing->owner_id) {
            throw ApiException::badRequest('You cannot report your own listing.', 'cannot_report_own');
        }

        $existing = Report::query()
            ->where('reporter_id', $reporter->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($existing) {
            throw ApiException::conflict('You have already reported this listing.', 'already_reported');
        }

        return Report::create([
            'reporter_id' => $reporter->id,
            'listing_id' => $listing->id,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
            'status' => Report::STATUS_OPEN,
        ]);
    }

    // --- Tracking --------------------------------------------------------

    public function recordContactView(Listing $listing, ?User $viewer, ?string $fingerprint): int
    {
        $attributes = $viewer
            ? ['listing_id' => $listing->id, 'viewer_id' => $viewer->id]
            : ['listing_id' => $listing->id, 'viewer_fingerprint' => $fingerprint];

        $view = ContactView::firstOrCreate($attributes);

        if ($view->wasRecentlyCreated) {
            $listing->increment('contact_view_count');
        }

        return (int) $listing->contact_view_count;
    }

    private function recordVisit(Listing $listing, ?User $viewer, ?string $fingerprint): void
    {
        $listing->increment('view_count');

        $today = now()->toDateString();
        $attributes = $viewer
            ? ['listing_id' => $listing->id, 'visitor_id' => $viewer->id, 'visited_on' => $today]
            : ['listing_id' => $listing->id, 'visitor_fingerprint' => $fingerprint, 'visited_on' => $today];

        // Daily dedupe; ignore unique-constraint races.
        try {
            ListingVisit::firstOrCreate($attributes, ['source' => request('source')]);
        } catch (\Throwable) {
            // visit already recorded for this visitor today
        }
    }

    // --- Internals -------------------------------------------------------

    private function assertWithinPlanLimit(User $user): void
    {
        $subscription = $user->activeSubscription();
        $limit = $subscription?->listingLimit() ?? config('subscription.plans.free.listing_limit');

        if ($limit === null) {
            return; // unlimited
        }

        $current = $this->listings->countActiveForOwner($user->id);

        if ($current >= $limit) {
            throw ApiException::forbidden(
                "Your current plan allows up to {$limit} active listings. Upgrade to add more.",
                'plan_limit_reached',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function touchesReviewableFields(array $data): bool
    {
        return (bool) array_intersect(
            array_keys($data),
            ['title', 'description', 'rent', 'address', 'area_name', 'images'],
        );
    }

    /** Keep the MySQL spatial POINT column in sync with the decimal columns. */
    private function syncGeoPoint(Listing $listing): void
    {
        if (DB::getDriverName() !== 'mysql' || ! $listing->hasLocation()) {
            return;
        }

        DB::statement(
            'UPDATE listings SET geo = ST_SRID(POINT(?, ?), 4326) WHERE id = ?',
            [$listing->longitude, $listing->latitude, $listing->id],
        );
    }
}
