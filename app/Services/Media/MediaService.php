<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\Media;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class MediaService
{
    public function __construct(private ImageService $images) {}

    public function upload(User $user, UploadedFile $file, string $folder = 'library'): Media
    {
        $stored = $this->images->upload($file, $folder);

        return Media::create([
            'owner_id' => $user->id,
            'url' => $stored['url'],
            'public_id' => $stored['public_id'],
            'disk' => $stored['disk'],
            'type' => str_starts_with((string) $file->getMimeType(), 'image/') ? 'image' : 'file',
            'mime' => $file->getMimeType(),
            'size' => $file->getSize() ?: 0,
        ]);
    }

    /**
     * @return LengthAwarePaginator<Media>
     */
    public function list(User $user, int $perPage, int $page): LengthAwarePaginator
    {
        return Media::query()
            ->when(! $user->isStaff(), fn ($q) => $q->where('owner_id', $user->id))
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    public function delete(User $user, Media $media): void
    {
        // Owners delete their own; staff may delete any.
        $this->images->delete($media->public_id, $media->disk, $media->url);
        $media->delete();
    }
}
