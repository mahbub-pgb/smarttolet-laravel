<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Sms\Contracts\SmsClientInterface;

/**
 * Captures sent SMS so tests can assert delivery and extract the OTP exactly
 * as a real handset would receive it (the API never returns the code).
 */
class SpySmsClient implements SmsClientInterface
{
    /** @var array<int, array{to: string, message: string}> */
    public array $sent = [];

    public function send(string $to, string $message): bool
    {
        $this->sent[] = ['to' => $to, 'message' => $message];

        return true;
    }

    public function name(): string
    {
        return 'spy';
    }

    public function lastMessage(): ?string
    {
        return $this->sent ? end($this->sent)['message'] : null;
    }

    /** Extract the numeric code from the most recent message. */
    public function lastCode(): ?string
    {
        $message = $this->lastMessage();
        if ($message === null) {
            return null;
        }

        return preg_match('/\b(\d{4,8})\b/', $message, $m) ? $m[1] : null;
    }
}
