<?php

declare(strict_types=1);

namespace App\Services\Sms\Drivers;

use App\Services\Sms\Contracts\SmsClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * BulkSMSBD HTTP API driver. Credentials/sender are injected so the
 * SettingsService can override them at runtime.
 */
class BulkSmsBdClient implements SmsClientInterface
{
    public function __construct(
        private string $endpoint,
        private ?string $apiKey,
        private string $senderId,
    ) {}

    public function send(string $to, string $message): bool
    {
        if (empty($this->apiKey)) {
            Log::warning('[SMS:bulksmsbd] missing API key; message not sent', ['to' => $to]);

            return false;
        }

        try {
            $response = Http::asForm()->timeout(15)->get($this->endpoint, [
                'api_key' => $this->apiKey,
                'type' => 'text',
                'number' => $this->normalize($to),
                'senderid' => $this->senderId,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('[SMS:bulksmsbd] send failed', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (Throwable $e) {
            Log::error('[SMS:bulksmsbd] exception', ['to' => $to, 'error' => $e->getMessage()]);
        }

        return false;
    }

    public function name(): string
    {
        return 'bulksmsbd';
    }

    /** BulkSMSBD expects 8801XXXXXXXXX format. */
    private function normalize(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (str_starts_with($digits, '880')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '88'.$digits;
        }

        return '880'.$digits;
    }
}
