<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsClientInterface;
use Illuminate\Support\Facades\Log;

/**
 * Logs the message instead of sending it. Used in local/dev/test. The OTP is
 * NEVER returned to the API; it only appears in the application log here.
 */
class MockSmsClient implements SmsClientInterface
{
    public function send(string $to, string $message): bool
    {
        Log::channel(config('logging.default'))->info('[SMS:mock] message dispatched', [
            'to' => $to,
            'message' => $message,
        ]);

        return true;
    }

    public function name(): string
    {
        return 'mock';
    }
}
