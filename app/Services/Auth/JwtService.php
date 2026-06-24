<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\User;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use stdClass;
use Throwable;

/**
 * Issues and validates JWT access and refresh tokens.
 *
 * - Access token (type=access): short-lived, sent as a Bearer header.
 * - Refresh token (type=refresh): long-lived, carries the user's token_version
 *   so that bumping the column invalidates every outstanding refresh token.
 */
class JwtService
{
    private string $secret;

    private string $algo;

    public function __construct()
    {
        $secret = (string) config('jwt.secret');

        if ($secret === '') {
            throw new \RuntimeException('JWT secret is not configured (set JWT_SECRET).');
        }

        $this->secret = $secret;
        $this->algo = (string) config('jwt.algo', 'HS256');
    }

    /**
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int}
     */
    public function issueTokens(User $user): array
    {
        $accessTtl = (int) config('jwt.access_ttl');

        return [
            'access_token' => $this->issueAccessToken($user),
            'refresh_token' => $this->issueRefreshToken($user),
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
        ];
    }

    public function issueAccessToken(User $user): string
    {
        $now = time();

        return JWT::encode([
            'iss' => (string) config('jwt.issuer'),
            'sub' => (string) $user->id,
            'type' => 'access',
            'role' => $user->role->value,
            'iat' => $now,
            'exp' => $now + (int) config('jwt.access_ttl'),
        ], $this->secret, $this->algo);
    }

    public function issueRefreshToken(User $user): string
    {
        $now = time();

        return JWT::encode([
            'iss' => (string) config('jwt.issuer'),
            'sub' => (string) $user->id,
            'type' => 'refresh',
            'tv' => (int) $user->token_version,
            'iat' => $now,
            'exp' => $now + (int) config('jwt.refresh_ttl'),
        ], $this->secret, $this->algo);
    }

    /**
     * Decode and validate a token, returning its claims. Throws ApiException
     * (401) on any failure.
     */
    public function decode(string $token): stdClass
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (ExpiredException) {
            throw ApiException::unauthorized('Token has expired.', 'token_expired');
        } catch (SignatureInvalidException) {
            throw ApiException::unauthorized('Token signature is invalid.', 'token_invalid');
        } catch (Throwable) {
            throw ApiException::unauthorized('Token is invalid.', 'token_invalid');
        }
    }

    public function decodeAccess(string $token): stdClass
    {
        $claims = $this->decode($token);

        if (($claims->type ?? null) !== 'access') {
            throw ApiException::unauthorized('Expected an access token.', 'token_invalid');
        }

        return $claims;
    }

    public function decodeRefresh(string $token): stdClass
    {
        $claims = $this->decode($token);

        if (($claims->type ?? null) !== 'refresh') {
            throw ApiException::unauthorized('Expected a refresh token.', 'token_invalid');
        }

        return $claims;
    }
}
