<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_request_never_returns_the_code(): void
    {
        $response = $this->postJson('/api/v1/auth/otp/request', ['mobile' => '01712345678']);

        $response->assertOk()->assertJson(['success' => true]);

        // The code must NOT appear anywhere in the response body.
        $body = $response->getContent();
        $this->assertStringNotContainsString((string) $this->sms->lastCode(), $body);
        $this->assertArrayNotHasKey('devOtp', $response->json('data') ?? []);

        // But it WAS delivered via SMS.
        $this->assertNotNull($this->sms->lastCode());
    }

    public function test_verify_creates_user_with_free_subscription_and_returns_tokens(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['mobile' => '01712345678'])->assertOk();
        $code = $this->sms->lastCode();

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'mobile' => '01712345678',
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'access_token', 'refresh_token']]);

        $user = User::where('mobile', '01712345678')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_phone_verified);
        $this->assertSame('free', $user->currentPlan());
    }

    public function test_wrong_code_is_rejected_and_locks_out_after_max_attempts(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['mobile' => '01712345678'])->assertOk();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/otp/verify', ['mobile' => '01712345678', 'code' => '000000'])
                ->assertStatus(422)
                ->assertJson(['success' => false, 'code' => 'otp_invalid']);
        }

        // 6th attempt is locked out.
        $this->postJson('/api/v1/auth/otp/verify', ['mobile' => '01712345678', 'code' => '000000'])
            ->assertStatus(429)
            ->assertJson(['code' => 'otp_locked']);
    }

    public function test_resend_cooldown_is_enforced(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['mobile' => '01712345678'])->assertOk();

        $this->postJson('/api/v1/auth/otp/request', ['mobile' => '01712345678'])
            ->assertStatus(429)
            ->assertJson(['code' => 'otp_cooldown']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401)->assertJson(['code' => 'unauthenticated']);
    }

    public function test_login_by_email_and_logout_revokes_refresh_tokens(): void
    {
        $user = User::factory()->create(['mobile' => '01799999999', 'email' => 'jo@example.com']);
        // factory password is "password"

        $login = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'jo@example.com',
            'password' => 'password',
        ])->assertOk();

        $refresh = $login->json('data.refresh_token');
        $this->assertNotNull($refresh);

        // Logout bumps token_version → old refresh token is rejected.
        $this->actingAsJwt($user->fresh())
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $refresh])
            ->assertStatus(401)
            ->assertJson(['code' => 'token_revoked']);
    }
}
