<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\SavedSearchResource;
use App\Services\Engagement\FavoriteService;
use App\Services\Engagement\SavedSearchService;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(
        private FavoriteService $favorites,
        private SavedSearchService $savedSearches,
        private NotificationService $notifications,
    ) {}

    // --- Favorites -------------------------------------------------------

    public function favorites(Request $request): JsonResponse
    {
        $paginator = $this->favorites->listListings(
            $request->user(),
            (int) $request->integer('limit', 15),
            (int) $request->integer('page', 1),
        );

        return $this->paginatedResponse($paginator, 'OK', fn ($items) => ListingResource::collection($items));
    }

    public function addFavorite(Request $request): JsonResponse
    {
        $data = $request->validate(['listing_id' => ['required', 'integer']]);
        $this->favorites->add($request->user(), (int) $data['listing_id']);

        return $this->created(null, 'Added to favorites.');
    }

    public function removeFavorite(Request $request, int $listingId): JsonResponse
    {
        $this->favorites->remove($request->user(), $listingId);

        return $this->noContentResponse('Removed from favorites.');
    }

    // --- Saved searches --------------------------------------------------

    public function savedSearches(Request $request): JsonResponse
    {
        return $this->ok(
            SavedSearchResource::collection($this->savedSearches->list($request->user())),
            'OK',
        );
    }

    public function createSavedSearch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'params' => ['required', 'array'],
            'notify' => ['sometimes', 'boolean'],
        ]);

        $saved = $this->savedSearches->create(
            $request->user(),
            $data['name'],
            $data['params'],
            (bool) ($data['notify'] ?? false),
        );

        return $this->created(new SavedSearchResource($saved), 'Search saved.');
    }

    public function deleteSavedSearch(Request $request, int $id): JsonResponse
    {
        $this->savedSearches->delete($request->user(), $id);

        return $this->noContentResponse('Saved search deleted.');
    }

    // --- Notifications ---------------------------------------------------

    public function notifications(Request $request): JsonResponse
    {
        $paginator = $this->notifications->paginateFor(
            $request->user(),
            (int) $request->integer('limit', 20),
            (int) $request->integer('page', 1),
            $request->boolean('unread'),
        );

        return $this->paginatedResponse(
            $paginator,
            'OK',
            fn ($items) => NotificationResource::collection($items),
        )->header('X-Unread-Count', (string) $this->notifications->unreadCount($request->user()));
    }

    public function readNotification(Request $request, int $id): JsonResponse
    {
        return $this->ok(new NotificationResource($this->notifications->markRead($request->user(), $id)), 'Marked read.');
    }

    public function readAllNotifications(Request $request): JsonResponse
    {
        $count = $this->notifications->markAllRead($request->user());

        return $this->ok(['updated' => $count], 'All notifications marked read.');
    }
}
