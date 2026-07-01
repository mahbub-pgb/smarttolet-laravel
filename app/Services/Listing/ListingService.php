<?php

declare(strict_types=1);

namespace App\Services\Listing;

use App\Enums\Role;
use App\Exceptions\ApiException;
use App\Models\ContactView;
use App\Models\Listing;
use App\Models\ListingVisit;
use App\Models\Media;
use App\Models\Report;
use App\Models\User;
use App\Repositories\Contracts\ListingRepositoryInterface;
use App\Services\Geo\GeoService;
use App\Services\Media\ImageService;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ListingService
{
    /** Days a listing stays live after approval before needing renewal. */
    private const LIFETIME_DAYS = 30;

    /** Hard cap on stored images per listing. */
    private const MAX_IMAGES = 10;

    public function __construct(
        private ListingRepositoryInterface $listings,
        private ImageService $images,
        private NotificationService $notifications,
        private GeoService $geo,
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
     * @param  array<int, UploadedFile>  $files
     */
    public function create(User $user, array $data, array $files = []): Listing
    {
        $this->assertWithinPlanLimit($user);

        $uploaded = $files ? $this->images->uploadMany($files) : ($data['images'] ?? []);
        $this->recordMedia($user, $uploaded);
        $picked = $this->resolvePickedMedia($user, $data['picked'] ?? []);
        $images = array_slice($this->dedupeByUrl([...$picked, ...$uploaded]), 0, self::MAX_IMAGES);

        [$address, $areaName] = $this->resolveLocation($data);

        // Admins / super admins publish immediately — no review queue.
        $autoPublish = $this->canSelfPublish($user);
        $status = $autoPublish
            ? Listing::STATUS_APPROVED
            : (($data['as_draft'] ?? false) ? Listing::STATUS_DRAFT : Listing::STATUS_PENDING);

        $listing = $this->listings->create([
            'owner_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'rent' => $data['rent'],
            'advance_amount' => $data['advance_amount'] ?? null,
            'available_from' => $data['available_from'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? 0,
            'bathrooms' => $data['bathrooms'] ?? 0,
            'area_sqft' => $data['area_sqft'] ?? null,
            'balconies' => $data['balconies'] ?? 0,
            'floor_number' => $data['floor_number'] ?? null,
            'building_floors' => $data['building_floors'] ?? null,
            'area_name' => $areaName,
            'address' => $address,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'amenities' => $data['amenities'] ?? [],
            'occupancy_rules' => $data['occupancy_rules'] ?? [],
            'images' => $images,
            'video_tour_url' => $data['video_tour_url'] ?? null,
            'status' => $status,
            'approved_at' => $autoPublish ? now() : null,
            'expires_at' => $autoPublish ? now()->addDays(self::LIFETIME_DAYS) : null,
        ]);

        $this->syncGeoPoint($listing);
        $this->backfillOwnerLocation($user, $data['latitude'] ?? null, $data['longitude'] ?? null);

        return $listing;
    }

    /**
     * Seed the owner's profile location from a listing's pin the first time they
     * post one — so the next listing's map can default to it. Never overwrites a
     * location the user has already set.
     */
    private function backfillOwnerLocation(User $user, mixed $lat, mixed $lng): void
    {
        if ($lat === null || $lng === null) {
            return;
        }

        if ($user->latitude !== null && $user->longitude !== null) {
            return;
        }

        $user->forceFill([
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ])->save();
    }

    /** Admins and super admins publish their own listings without review. */
    private function canSelfPublish(User $user): bool
    {
        return $user->hasRole(Role::Admin, Role::SuperAdmin);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function update(Listing $listing, array $data, array $files = []): Listing
    {
        // Rebuild the image set only when media actually changed (uploads, picks
        // or removals). Leaving the key absent otherwise preserves the existing
        // images and keeps the reviewable-fields check unaffected.
        $removeImages = $data['remove_images'] ?? [];
        $picked = $this->resolvePickedMedia($listing->owner, $data['picked'] ?? []);

        if ($files || $picked || $removeImages) {
            $kept = $this->removeImages($listing, $removeImages);
            $uploaded = $files ? $this->images->uploadMany($files) : [];
            $this->recordMedia($listing->owner, $uploaded);
            $data['images'] = array_slice($this->dedupeByUrl([...$kept, ...$picked, ...$uploaded]), 0, self::MAX_IMAGES);
        }

        if (array_key_exists('latitude', $data)) {
            [$data['address'], $data['area_name']] = $this->resolveLocation($data, $listing);
        }

        // Status: the dashboard form sends `as_draft`, which means the owner is
        // editing and may only land on draft or pending (never self-publish).
        // Without it (e.g. the API), the legacy rule applies: editing an
        // approved listing's reviewable fields re-queues it for moderation.
        if (array_key_exists('as_draft', $data)) {
            if ($listing->owner && $this->canSelfPublish($listing->owner)) {
                // Publishers stay published; their edits never re-enter review.
                $data['status'] = Listing::STATUS_APPROVED;
                if ($listing->approved_at === null) {
                    $data['approved_at'] = now();
                    $data['expires_at'] = now()->addDays(self::LIFETIME_DAYS);
                }
            } elseif ($listing->status === Listing::STATUS_APPROVED) {
                if ($this->touchesReviewableFields($data)) {
                    $data['status'] = Listing::STATUS_PENDING;
                }
            } else {
                $data['status'] = $data['as_draft'] ? Listing::STATUS_DRAFT : Listing::STATUS_PENDING;
            }
        } elseif ($listing->status === Listing::STATUS_APPROVED && $this->touchesReviewableFields($data)) {
            $data['status'] = Listing::STATUS_PENDING;
        }

        // Strip control keys that are not real columns before mass-assigning.
        unset($data['picked'], $data['remove_images'], $data['as_draft']);

        $listing->fill($data)->save();
        $this->syncGeoPoint($listing);

        return $listing->refresh();
    }

    /**
     * Resolve media-library picks (Media ids owned by the user) into the stored
     * image shape. Silently drops ids the user does not own.
     *
     * @param  array<int, int|string>  $ids
     * @return array<int, array{url: string, public_id: string|null, disk: string}>
     */
    private function resolvePickedMedia(?User $owner, array $ids): array
    {
        if (! $owner || $ids === []) {
            return [];
        }

        return Media::query()
            ->where('owner_id', $owner->id)
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (Media $m) => ['url' => $m->url, 'public_id' => $m->public_id, 'disk' => $m->disk])
            ->all();
    }

    /**
     * Register freshly-uploaded listing images in the owner's media library so
     * they can be re-picked on future listings. Skips ones already recorded.
     *
     * @param  array<int, array{url: string, public_id: string|null, disk: string}>  $images
     */
    private function recordMedia(?User $owner, array $images): void
    {
        if (! $owner) {
            return;
        }

        foreach ($images as $image) {
            if (empty($image['url'])) {
                continue;
            }

            Media::firstOrCreate(
                ['owner_id' => $owner->id, 'url' => $image['url']],
                [
                    'public_id' => $image['public_id'] ?? null,
                    'disk' => $image['disk'] ?? 'public',
                    'type' => 'image',
                ],
            );
        }
    }

    /**
     * Return the listing's images minus any whose url is in $urls, deleting the
     * removed files from storage.
     *
     * @param  array<int, string>  $urls
     * @return array<int, array<string, mixed>>
     */
    private function removeImages(Listing $listing, array $urls): array
    {
        $current = $listing->images ?? [];
        if ($urls === []) {
            return $current;
        }

        $remove = array_flip($urls);
        $kept = [];

        foreach ($current as $image) {
            if (isset($remove[$image['url'] ?? ''])) {
                $this->deleteFileUnlessInLibrary($image);

                continue;
            }
            $kept[] = $image;
        }

        return $kept;
    }

    /**
     * Delete an image's underlying file, but only when it isn't kept in any
     * user's media library. The library is a reusable collection, so its files
     * must survive listing edits/deletion — otherwise picked images and library
     * thumbnails break.
     *
     * @param  array<string, mixed>  $image
     */
    private function deleteFileUnlessInLibrary(array $image): void
    {
        $url = $image['url'] ?? '';

        if ($url !== '' && Media::query()->where('url', $url)->exists()) {
            return; // still referenced by the media library — keep the file
        }

        $this->images->delete($image['public_id'] ?? null, $image['disk'] ?? 'cloudinary', $url);
    }

    /**
     * Resolve the human address + area for a coordinate. Uses the submitted
     * values when present, otherwise reverse-geocodes the pin (best effort).
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string}
     */
    private function resolveLocation(array $data, ?Listing $existing = null): array
    {
        $address = trim((string) ($data['address'] ?? '')) ?: (string) ($existing->address ?? '');
        $area = trim((string) ($data['area_name'] ?? '')) ?: (string) ($existing->area_name ?? '');

        $lat = $data['latitude'] ?? $existing?->latitude;
        $lng = $data['longitude'] ?? $existing?->longitude;

        if (($address === '' || $area === '') && $lat !== null && $lng !== null) {
            $geo = $this->geo->reverseGeocode((float) $lat, (float) $lng);
            $address = $address ?: (string) ($geo['formatted_address'] ?? 'Location pinned on map');
            $area = $area ?: (string) ($geo['area_name'] ?? 'Unknown area');
        }

        return [$address ?: 'Location pinned on map', $area ?: 'Unknown area'];
    }

    public function delete(Listing $listing): void
    {
        foreach ($listing->images ?? [] as $image) {
            $this->deleteFileUnlessInLibrary($image);
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

    /**
     * Drop duplicate images sharing the same url, preserving order.
     *
     * @param  array<int, array<string, mixed>>  $images
     * @return array<int, array<string, mixed>>
     */
    private function dedupeByUrl(array $images): array
    {
        $seen = [];
        $out = [];

        foreach ($images as $image) {
            $url = $image['url'] ?? null;
            if ($url === null || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $out[] = $image;
        }

        return $out;
    }

    /** Staff action: send a listing back to draft (unpublished). */
    public function markDraft(Listing $listing): Listing
    {
        $listing->forceFill([
            'status' => Listing::STATUS_DRAFT,
            'approved_at' => null,
            'expires_at' => null,
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

    public function moderate(Listing $listing, string $action, ?string $reason = null, ?User $moderator = null): Listing
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

            // Keep a full history of every rejection (who, when, why).
            $listing->rejections()->create([
                'moderator_id' => $moderator?->id,
                'reason' => (string) $reason,
            ]);

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

    /**
     * Record a listing visit, incrementing view_count only for a genuinely new
     * visitor-per-day (so reloads by the same user/guest don't inflate views).
     * Guests are identified by an opaque fingerprint (cookie token or ip+ua).
     */
    public function recordVisit(Listing $listing, ?User $viewer, ?string $fingerprint): void
    {
        $today = now()->toDateString();
        $attributes = $viewer
            ? ['listing_id' => $listing->id, 'visitor_id' => $viewer->id, 'visited_on' => $today]
            : ['listing_id' => $listing->id, 'visitor_fingerprint' => $fingerprint, 'visited_on' => $today];

        // Daily dedupe; ignore unique-constraint races. Only count the first
        // visit of the day for this visitor.
        try {
            $visit = ListingVisit::firstOrCreate($attributes, ['source' => request('source')]);

            if ($visit->wasRecentlyCreated) {
                $listing->increment('view_count');
            }
        } catch (\Throwable) {
            // visit already recorded for this visitor today (unique race)
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
