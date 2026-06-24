<?php

namespace Tests;

use App\Models\User;
use App\Services\Auth\Contracts\OtpRepository;
use App\Services\Auth\JwtService;
use App\Services\Sms\Contracts\SmsClientInterface;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\ArrayOtpRepository;
use Tests\Support\SpySmsClient;

abstract class TestCase extends BaseTestCase
{
    protected SpySmsClient $sms;

    protected function setUp(): void
    {
        parent::setUp();

        // Swap Redis OTP storage + SMS delivery for in-memory test doubles so
        // the auth/OTP flow runs without Redis or a live SMS provider.
        $this->app->instance(OtpRepository::class, new ArrayOtpRepository());

        $this->sms = new SpySmsClient();
        $this->app->instance(SmsClientInterface::class, $this->sms);
    }

    /** Authenticate the given user by setting a JWT bearer token on requests. */
    protected function actingAsJwt(User $user): static
    {
        $token = app(JwtService::class)->issueAccessToken($user);
        $this->withHeader('Authorization', 'Bearer '.$token);

        return $this;
    }
}
