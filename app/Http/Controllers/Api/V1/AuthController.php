<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailOtpRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RequestPhoneOtpRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\VerifyEmailOtpRequest;
use App\Http\Requests\Auth\VerifyPhoneOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    /**
     * POST /auth/otp/request
     *
     * @OA\Post(
     *     path="/auth/otp/request",
     *     tags={"Auth"},
     *     summary="Request a phone verification OTP (delivered via SMS only)",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"mobile"},
     *         @OA\Property(property="mobile", type="string", example="01712345678")
     *     )),
     *     @OA\Response(response=200, description="Code sent", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=429, description="Cooldown / rate limited", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function requestOtp(RequestPhoneOtpRequest $request): JsonResponse
    {
        $this->auth->requestPhoneOtp($request->mobile());

        // The code is delivered via SMS only and is never returned here.
        return $this->ok(null, 'Verification code sent.');
    }

    /**
     * POST /auth/otp/verify
     *
     * @OA\Post(
     *     path="/auth/otp/verify",
     *     tags={"Auth"},
     *     summary="Verify the phone OTP and receive access + refresh tokens",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"mobile","code"},
     *         @OA\Property(property="mobile", type="string", example="01712345678"),
     *         @OA\Property(property="code", type="string", example="123456")
     *     )),
     *     @OA\Response(response=200, description="Verified", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=422, description="Invalid code", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function verifyOtp(VerifyPhoneOtpRequest $request): JsonResponse
    {
        $result = $this->auth->verifyPhoneOtp(
            (string) $request->validated('mobile'),
            (string) $request->validated('code'),
        );

        return $this->tokenResponse($result['user'], $result['tokens'], 'Phone verified.');
    }

    /** POST /auth/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            (string) $request->validated('identifier'),
            (string) $request->validated('password'),
        );

        return $this->tokenResponse($result['user'], $result['tokens'], 'Logged in.');
    }

    /** POST /auth/refresh */
    public function refresh(Request $request): JsonResponse
    {
        $token = $this->readRefreshToken($request);

        if ($token === null) {
            throw ApiException::unauthorized('Refresh token missing.', 'refresh_missing');
        }

        $result = $this->auth->refresh($token);

        return $this->tokenResponse($result['user'], $result['tokens'], 'Token refreshed.');
    }

    /** POST /auth/logout */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->auth->logout($user);

        return $this->ok(null, 'Logged out.')
            ->withCookie($this->forgetRefreshCookie());
    }

    /**
     * GET /auth/me
     *
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Auth"},
     *     summary="Get the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/ApiSuccess")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return $this->ok(new UserResource($request->user()), 'OK');
    }

    /** PUT /auth/profile */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->auth->updateProfile($request->user(), $request->validated());

        return $this->ok(new UserResource($user), 'Profile updated.');
    }

    /** POST /auth/email/otp/request */
    public function requestEmailOtp(EmailOtpRequest $request): JsonResponse
    {
        $this->auth->requestEmailOtp($request->user(), (string) $request->validated('email'));

        return $this->ok(null, 'Verification code sent to email.');
    }

    /** POST /auth/email/otp/verify */
    public function verifyEmailOtp(VerifyEmailOtpRequest $request): JsonResponse
    {
        $user = $this->auth->verifyEmailOtp(
            $request->user(),
            (string) $request->validated('email'),
            (string) $request->validated('code'),
        );

        return $this->ok(new UserResource($user), 'Email verified.');
    }

    // --- Helpers ---------------------------------------------------------

    /**
     * @param  array<string, mixed>  $tokens
     */
    private function tokenResponse(User $user, array $tokens, string $message): JsonResponse
    {
        $body = [
            'user' => (new UserResource($user))->resolve(request()),
            'access_token' => $tokens['access_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            // Also returned in the body for non-browser API clients.
            'refresh_token' => $tokens['refresh_token'],
        ];

        return $this->ok($body, $message)
            ->withCookie($this->makeRefreshCookie($tokens['refresh_token']));
    }

    private function readRefreshToken(Request $request): ?string
    {
        $cookieName = (string) config('jwt.refresh_cookie.name');

        return $request->cookie($cookieName)
            ?? $request->input('refresh_token')
            ?? $request->bearerToken();
    }

    private function makeRefreshCookie(string $token): Cookie
    {
        $config = config('jwt.refresh_cookie');

        return new Cookie(
            name: $config['name'],
            value: $token,
            expire: time() + (int) config('jwt.refresh_ttl'),
            path: $config['path'],
            domain: null,
            secure: (bool) $config['secure'],
            httpOnly: (bool) $config['http_only'],
            raw: false,
            sameSite: $config['same_site'],
        );
    }

    private function forgetRefreshCookie(): Cookie
    {
        $config = config('jwt.refresh_cookie');

        return new Cookie(
            name: $config['name'],
            value: '',
            expire: time() - 3600,
            path: $config['path'],
            domain: null,
            secure: (bool) $config['secure'],
            httpOnly: (bool) $config['http_only'],
            raw: false,
            sameSite: $config['same_site'],
        );
    }
}
