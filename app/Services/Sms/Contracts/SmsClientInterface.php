<?php

declare(strict_types=1);

namespace App\Services\Sms\Contracts;

interface SmsClientInterface
{
    /**
     * Send an SMS. Returns true on success. Implementations should not throw
     * for ordinary delivery failures — log and return false instead.
     */
    public function send(string $to, string $message): bool;

    public function name(): string;
}
