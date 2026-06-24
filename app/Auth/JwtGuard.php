<?php

declare(strict_types=1);

namespace App\Auth;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Services\Auth\JwtService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;

/**
 * Stateless guard that authenticates requests from a JWT Bearer access token.
 * The user is resolved lazily from the token on first access and cached for
 * the request.
 */
class JwtGuard implements Guard
{
    use Macroable;

    protected ?Authenticatable $user = null;

    protected bool $resolved = false;

    public function __construct(
        protected UserProvider $provider,
        protected Request $request,
        protected JwtService $jwt,
    ) {}

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;

        $token = $this->bearerToken();

        if ($token === null) {
            return null;
        }

        $claims = $this->jwt->decodeAccess($token);

        /** @var User|null $user */
        $user = $this->provider->retrieveById($claims->sub ?? null);

        if ($user === null) {
            return null;
        }

        if ($user->is_suspended) {
            throw ApiException::forbidden('Your account has been suspended.', 'account_suspended');
        }

        return $this->user = $user;
    }

    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validate(array $credentials = []): bool
    {
        return false; // credential validation is handled by the AuthService
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
        $this->resolved = true;
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    protected function bearerToken(): ?string
    {
        $token = $this->request->bearerToken();

        return $token !== null && $token !== '' ? $token : null;
    }
}
