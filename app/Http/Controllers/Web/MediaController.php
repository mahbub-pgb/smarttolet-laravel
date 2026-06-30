<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Session-authenticated media library for the server-rendered admin. Backs the
 * reusable "central media" picker (public/js/media-library.js): list previously
 * uploaded images, or upload a new one. Reuses MediaService, so storage +
 * server-side compression are identical to the API path.
 */
class MediaController extends Controller
{
    public function __construct(private MediaService $media) {}

    /** GET /admin/media — paginated JSON list of images for the picker. */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->media->list(
            $request->user(),
            (int) $request->integer('limit', 24),
            (int) $request->integer('page', 1),
        );

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Media $m) => [
                'id' => $m->id,
                'url' => $m->url,
                'type' => $m->type,
            ])->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /** POST /admin/media — upload one image, return its stored URL. */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ]);

        $media = $this->media->upload($request->user(), $request->file('file'), 'library');

        return response()->json([
            'id' => $media->id,
            'url' => $media->url,
            'type' => $media->type,
        ], 201);
    }
}
