<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Services\Geo\GeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicController extends Controller
{
    public function __construct(private GeoService $geo) {}

    /** GET /public/places/nearby?lat=&lng=&type=&radius= */
    public function nearbyPlaces(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'type' => ['sometimes', 'nullable', 'string', 'max:40'],
            'radius' => ['sometimes', 'nullable', 'integer', 'between:100,50000'],
        ])->validate();

        $places = $this->geo->nearbyPlaces(
            (float) $data['lat'],
            (float) $data['lng'],
            $data['type'] ?? null,
            isset($data['radius']) ? (int) $data['radius'] : null,
        );

        return $this->ok($places, 'OK');
    }

    /** GET /public/advertisements?placement= — active ad creatives. */
    public function advertisements(Request $request): JsonResponse
    {
        $ads = Advertisement::query()
            ->activeNow()
            ->when($request->filled('placement'), fn ($q) => $q->where('placement', $request->string('placement')->value()))
            ->latest()
            ->limit(20)
            ->get(['id', 'title', 'image', 'target_url', 'placement']);

        return $this->ok($ads, 'OK');
    }
}
