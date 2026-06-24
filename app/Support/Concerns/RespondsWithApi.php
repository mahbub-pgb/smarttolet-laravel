<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use App\Support\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Thin controller-facing wrapper around {@see ApiResponse}. Keeps controllers
 * terse: `return $this->ok($resource);`
 */
trait RespondsWithApi
{
    /**
     * @param  array<string, mixed>  $meta
     */
    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): JsonResponse
    {
        return ApiResponse::success($data, $message, $status, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function created(mixed $data = null, string $message = 'Created', array $meta = []): JsonResponse
    {
        return ApiResponse::success($data, $message, 201, $meta);
    }

    protected function noContentResponse(string $message = 'Deleted'): JsonResponse
    {
        return ApiResponse::success(null, $message, 200);
    }

    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $message = 'OK',
        ?callable $transform = null,
    ): JsonResponse {
        return ApiResponse::paginated($paginator, $message, $transform);
    }
}
