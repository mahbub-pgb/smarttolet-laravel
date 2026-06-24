<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Builds the standard response envelope:
 *
 *   success: { "success": true,  "message": "OK", "data": {...}, "meta": {...} }
 *   error:   { "success": false, "message": "...", "code": "...", "details": {...} }
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => self::resolveData($data),
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    public static function error(
        string $message,
        int $status = 400,
        string $code = 'error',
        ?array $details = null,
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
            'code' => $code,
        ];

        if ($details !== null) {
            $payload['details'] = $details;
        }

        return response()->json($payload, $status);
    }

    /**
     * Wrap a length-aware paginator. The list items live in `data` while the
     * pagination details live in `meta`. An optional transform callback maps
     * the underlying collection (e.g. into an API resource collection).
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'OK',
        ?callable $transform = null,
    ): JsonResponse {
        $items = $transform
            ? $transform($paginator->getCollection())
            : $paginator->getCollection();

        return self::success(
            data: self::resolveData($items),
            message: $message,
            meta: self::paginationMeta($paginator),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'limit' => $paginator->perPage(),
            'total' => $paginator->total(),
            'totalPages' => $paginator->lastPage(),
            'hasNextPage' => $paginator->hasMorePages(),
            'hasPrevPage' => $paginator->currentPage() > 1,
        ];
    }

    private static function resolveData(mixed $data): mixed
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->resolve(request());
        }

        return $data;
    }
}
