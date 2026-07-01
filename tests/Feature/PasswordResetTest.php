<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reset_password_via_sms_otp(): void
    {
        $user = User::factory()->create(['mobile' => '01712345678']);
        $originalVersion = $user->token_version;

        // Step 1 — request a reset code.
        $this->post('/password/forgot', ['mobile' => '01712345678'])
            ->assertRedirect(route('password.verify'));

        $code = $this->sms->lastCode();
        $this->assertNotNull($code);

        // Step 2 — verify the code.
        $this->post('/password/verify', ['code' => $code])
            ->assertRedirect(route('password.reset'));

        // Step 3 — set a new password.
        $this->post('/password/reset', [
            'password' => 'brandnewpass',
            'password_confirmation' => 'brandnewpass',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('brandnewpass', $user->password));
        // Existing API refresh tokens are invalidated.
        $this->assertSame($originalVersion + 1, $user->token_version);
    }

    public function test_forgot_password_rejects_an_unregistered_number(): void
    {
        $this->from(route('password.forgot'))
            ->post('/password/forgot', ['mobile' => '01700000000'])
            ->assertRedirect(route('password.forgot'))
            ->assertSessionHasErrors('mobile');

        $this->assertNull($this->sms->lastCode());
    }

    public function test_reset_code_requests_are_throttled_by_cooldown(): void
    {
        User::factory()->create(['mobile' => '01712345678']);

        $this->post('/password/forgot', ['mobile' => '01712345678'])
            ->assertRedirect(route('password.verify'));

        // An immediate second request is blocked by the OTP resend cooldown.
        $this->from(route('password.forgot'))
            ->post('/password/forgot', ['mobile' => '01712345678'])
            ->assertRedirect(route('password.forgot'))
            ->assertSessionHasErrors('mobile');
    }

    public function test_reset_steps_require_the_prior_step(): void
    {
        // Cannot reach the new-password form without verifying an OTP first.
        $this->get('/password/reset')->assertRedirect(route('password.forgot'));
        $this->get('/password/verify')->assertRedirect(route('password.forgot'));
    }
}
