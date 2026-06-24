<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * Domain exception that carries an HTTP status, a machine-readable code and
 * optional structured details. Thrown from services and rendered into the
 * standard error envelope by the global handler.
 */
class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $details
     */
    public function __construct(
        string $message,
        protected int $status = 400,
        protected string $errorCode = 'error',
        protected ?array $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->status, $this->errorCode, $this->details);
    }

    // --- Named constructors for common cases -----------------------------

    public static function badRequest(string $message = 'Bad request', string $code = 'bad_request', ?array $details = null): self
    {
        return new self($message, 400, $code, $details);
    }

    public static function unauthorized(string $message = 'Unauthenticated', string $code = 'unauthenticated'): self
    {
        return new self($message, 401, $code);
    }

    public static function forbidden(string $message = 'Forbidden', string $code = 'forbidden'): self
    {
        return new self($message, 403, $code);
    }

    public static function notFound(string $message = 'Resource not found', string $code = 'not_found'): self
    {
        return new self($message, 404, $code);
    }

    public static function conflict(string $message = 'Conflict', string $code = 'conflict', ?array $details = null): self
    {
        return new self($message, 409, $code, $details);
    }

    public static function unprocessable(string $message = 'Unprocessable entity', string $code = 'unprocessable', ?array $details = null): self
    {
        return new self($message, 422, $code, $details);
    }

    public static function tooManyRequests(string $message = 'Too many requests', string $code = 'rate_limited', ?array $details = null): self
    {
        return new self($message, 429, $code, $details);
    }
}
