<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Services\Settings\SettingsService;
use App\Services\Sms\Contracts\SmsClientInterface;
use App\Services\Sms\Drivers\BulkSmsBdClient;
use App\Services\Sms\Drivers\MockSmsClient;
use InvalidArgumentException;

/**
 * Resolves the active SMS driver. Provider + credentials come from the
 * SettingsService (DB -> env), so they can be reconfigured at runtime.
 */
class SmsManager
{
    public function __construct(private SettingsService $settings) {}

    public function driver(?string $name = null): SmsClientInterface
    {
        $name ??= (string) $this->settings->get('sms_provider', config('sms.default', 'mock'));

        return match ($name) {
            'mock' => new MockSmsClient(),
            'bulksmsbd' => new BulkSmsBdClient(
                endpoint: (string) config('sms.drivers.bulksmsbd.endpoint'),
                apiKey: $this->settings->get('sms_api_key', config('sms.drivers.bulksmsbd.api_key')),
                senderId: (string) $this->settings->get('sms_sender_id', config('sms.sender_id')),
            ),
            default => throw new InvalidArgumentException("Unsupported SMS driver [{$name}]."),
        };
    }
}
