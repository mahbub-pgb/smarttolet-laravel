<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\ModerateListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Services\Listing\ListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingModerationController extends Controller
{
    public function __construct(private ListingService $listings) {}

    /** GET /admin/listings/queue — pending moderation (and optional status filter). */
    public function queue(Request $request): JsonResponse
    {
        $status = $request->string('status', Listing::STATUS_PENDING)->value();

        $paginator = Listing::query()
            ->where('status', $status)
            ->with('owner:id,name,mobile')
            ->latest()
            ->paginate(
                perPage: (int) $request->integer('limit', 20),
                page: (int) $request->integer('page', 1),
            );

        return $this->paginatedResponse($paginator, 'OK', fn ($items) => ListingResource::collection($items));
    }

    /** PATCH /admin/listings/{listing}/moderate */
    public function moderate(ModerateListingRequest $request, Listing $listing): JsonResponse
    {
        $listing = $this->listings->moderate(
            $listing,
            $request->validated('action'),
            $request->validated('reason'),
        );

        return $this->ok(new ListingResource($listing), 'Listing moderated.');
    }
}
