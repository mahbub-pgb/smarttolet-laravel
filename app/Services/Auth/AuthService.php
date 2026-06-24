<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public const PURPOSE_PHONE = 'phone_verify';

    public const PURPOSE_EMAIL = 'email_verify';

    public function __construct(
        private UserRepositoryInterface $users,
        private OtpService $otp,
        private JwtService $jwt,
    ) {}

    // --- Phone registration ---------------------------------------------

    public function requestPhoneOtp(string $mobile): void
    {
        $user = $this->users->findByMobile($mobile);

        if ($user && $user->is_suspended) {
            throw ApiException::forbidden('This account is suspended.', 'account_suspended');
        }

        $this->otp->request(self::PURPOSE_PHONE, $mobile, $mobile);
    }

    /**
     * Verify the phone OTP. Creates a phone-verified user (with a Free
     * subscription) on first verification, then issues tokens.
     *
     * @return array{user: User, tokens: array<string, mixed>}
     */
    public function verifyPhoneOtp(string $mobile, string $code): array
    {
        $this->otp->verify(self::PURPOSE_PHONE, $mobile, $code);

        $user = DB::transaction(function () use ($mobile) {
            $user = $this->users->findByMobile($mobile);

            if (! $user) {
                $user = $this->users->create([
                    'mobile' => $mobile,
                    'role' => 'user',
                    'is_phone_verified' => true,
                ]);

                $this->createFreeSubscription($user);
            } elseif (! $user->is_phone_verified) {
                $user->is_phone_verified = true;
                $user->save();
            }

            $user->forceFill(['last_login_at' => now()])->save();

            return $user;
        });

        return [
            'user' => $user,
            'tokens' => $this->jwt->issueTokens($user),
        ];
    }

    // --- Login / session -------------------------------------------------

    /**
     * @return array{user: User, tokens: array<string, mixed>}
     */
    public function login(string $identifier, string $password): array
    {
        $user = $this->users->findByIdentifier($identifier);

        if (! $user || $user->password === null || ! Hash::check($password, $user->password)) {
            throw ApiException::unauthorized('Invalid credentials.', 'invalid_credentials');
        }

        if ($user->is_suspended) {
            throw ApiException::forbidden('This account is suspended.', 'account_suspended');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return [
            'user' => $user,
            'tokens' => $this->jwt->issueTokens($user),
        ];
    }

    /**
     * Rotate tokens from a refresh token, validating the token_version.
     *
     * @return array{user: User, tokens: array<string, mixed>}
     */
    public function refresh(string $refreshToken): array
    {
        $claims = $this->jwt->decodeRefresh($refreshToken);

        /** @var User|null $user */
        $user = $this->users->find((int) $claims->sub);

        if (! $user) {
            throw ApiException::unauthorized('Account no longer exists.', 'user_not_found');
        }

        if ((int) ($claims->tv ?? -1) !== (int) $user->token_version) {
            throw ApiException::unauthorized('Refresh token has been revoked.', 'token_revoked');
        }

        if ($user->is_suspended) {
            throw ApiException::forbidden('This account is suspended.', 'account_suspended');
        }

        return [
            'user' => $user,
            'tokens' => $this->jwt->issueTokens($user),
        ];
    }

    /**
     * Invalidate all refresh tokens by bumping the token_version.
     */
    public function logout(User $user): void
    {
        $user->increment('token_version');
    }

    // --- Profile ---------------------------------------------------------

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        if (array_key_exists('password', $data) && ! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Normalise geo if supplied.
        if (isset($data['latitude'], $data['longitude'])) {
            $user->latitude = (float) $data['latitude'];
            $user->longitude = (float) $data['longitude'];
        }
        unset($data['latitude'], $data['longitude']);

        $user->fill($data)->save();

        return $user->refresh();
    }

    // --- Email verification ---------------------------------------------

    public function requestEmailOtp(User $user, string $email): void
    {
        $existing = $this->users->findByEmail($email);
        if ($existing && $existing->id !== $user->id) {
            throw ApiException::conflict('That email is already in use.', 'email_taken');
        }

        $this->otp->request(self::PURPOSE_EMAIL, (string) $user->id, $email);
    }

    public function verifyEmailOtp(User $user, string $email, string $code): User
    {
        $this->otp->verify(self::PURPOSE_EMAIL, (string) $user->id, $code);

        $user->forceFill([
            'email' => $email,
            'is_email_verified' => true,
        ])->save();

        return $user->refresh();
    }

    private function createFreeSubscription(User $user): Subscription
    {
        return $user->subscriptions()->create([
            'plan' => config('subscription.default', 'free'),
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => null,
        ]);
    }
}
