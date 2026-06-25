<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Services\Settings\SettingsService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Handles image processing + storage. Images are resized with Intervention
 * Image, then uploaded to Cloudinary when configured, otherwise persisted to
 * the local "public" disk as a fallback.
 *
 * @phpstan-type StoredImage array{url: string, public_id: string|null, disk: string}
 */
class ImageService
{
    private const MAX_WIDTH = 1600;

    public function __construct(private SettingsService $settings) {}

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array{url: string, public_id: string|null, disk: string}>
     */
    public function uploadMany(array $files, string $folder = 'listings'): array
    {
        $stored = [];
        foreach ($files as $file) {
            $result = $this->upload($file, $folder);
            if ($result !== null) {
                $stored[] = $result;
            }
        }

        return $stored;
    }

    /**
     * @return array{url: string, public_id: string|null, disk: string}|null
     */
    public function upload(UploadedFile $file, string $folder = 'listings'): ?array
    {
        if ($this->cloudinaryConfigured()) {
            $cloud = $this->uploadToCloudinary($file, $folder);
            if ($cloud !== null) {
                return $cloud;
            }
            // Fall through to local storage on Cloudinary failure.
        }

        return $this->storeLocally($file, $folder);
    }

    public function delete(?string $publicId, string $disk, string $url): void
    {
        try {
            if ($disk === 'cloudinary' && $publicId && $this->cloudinaryConfigured()) {
                Cloudinary::destroy($publicId);

                return;
            }

            if ($disk === 'public') {
                $path = Str::after($url, '/storage/');
                Storage::disk('public')->delete($path);
            }
        } catch (Throwable $e) {
            Log::warning('[image] delete failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Resize/compress to a JPEG. Returns the processed binary, or null when the
     * GD extension is unavailable or processing fails (the caller then stores
     * the original file untouched, preserving its real format/extension).
     */
    private function resize(UploadedFile $file): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        try {
            $manager = ImageManager::gd();
            $image = $manager->read($file->getRealPath());
            $image->scaleDown(width: self::MAX_WIDTH);

            return (string) $image->toJpeg(quality: 82);
        } catch (Throwable $e) {
            Log::warning('[image] resize failed, using original', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{url: string, public_id: string|null, disk: string}|null
     */
    private function uploadToCloudinary(UploadedFile $file, string $folder): ?array
    {
        try {
            $response = Cloudinary::upload($file->getRealPath(), [
                'folder' => 'smart-to-let/'.$folder,
                'transformation' => [
                    ['width' => self::MAX_WIDTH, 'crop' => 'limit', 'quality' => 'auto'],
                ],
            ]);

            return [
                'url' => $response->getSecurePath(),
                'public_id' => $response->getPublicId(),
                'disk' => 'cloudinary',
            ];
        } catch (Throwable $e) {
            Log::error('[image] cloudinary upload failed, falling back to local', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Persist to the local "public" disk. Uses the resized JPEG when GD is
     * available, otherwise stores the original file with its real extension.
     *
     * @return array{url: string, public_id: string|null, disk: string}|null
     */
    private function storeLocally(UploadedFile $file, string $folder): ?array
    {
        try {
            $binary = $this->resize($file);

            if ($binary !== null) {
                $name = $folder.'/'.Str::uuid()->toString().'.jpg';
                Storage::disk('public')->put($name, $binary);
            } else {
                $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg';
                $name = $folder.'/'.Str::uuid()->toString().'.'.strtolower($ext);
                Storage::disk('public')->putFileAs($folder, $file, basename($name));
            }

            return [
                'url' => Storage::disk('public')->url($name),
                'public_id' => null,
                'disk' => 'public',
            ];
        } catch (Throwable $e) {
            Log::error('[image] local store failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function cloudinaryConfigured(): bool
    {
        return ! empty($this->settings->get('cloudinary_cloud_name', config('cloudinary.cloud_url')))
            || ! empty(env('CLOUDINARY_URL'));
    }
}
