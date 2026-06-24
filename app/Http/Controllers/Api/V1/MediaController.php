<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->media->list(
            $request->user(),
            (int) $request->integer('limit', 24),
            (int) $request->integer('page', 1),
        );

        return $this->paginatedResponse($paginator, 'OK');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'folder' => ['sometimes', 'string', 'max:40'],
        ]);

        $media = $this->media->upload(
            $request->user(),
            $request->file('file'),
            $request->string('folder', 'library')->value(),
        );

        return $this->created($media, 'Uploaded.');
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        $user = $request->user();

        if ($media->owner_id !== $user->id && ! $user->isStaff()) {
            throw ApiException::forbidden('You cannot delete this asset.', 'forbidden');
        }

        $this->media->delete($user, $media);

        return $this->noContentResponse('Asset deleted.');
    }
}
