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
            // BulkSMSBD's smsapi expects a POST with form fields. It returns
            // HTTP 200 even for logical errors, so success is determined by the
            // "response_code" in the body (202 = SMS Submitted Successfully).
            $response = Http::asForm()->timeout(15)->post($this->endpoint, [
                'api_key' => $this->apiKey,
                'senderid' => $this->senderId,
                'number' => $this->normalize($to),
                'message' => $message,
            ]);

            $code = $this->responseCode($response->body());

            if ($code === 202) {
                return true;
            }

            Log::error('[SMS:bulksmsbd] send failed', [
                'to' => $to,
                'status' => $response->status(),
                'response_code' => $code,
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

    /**
     * Extract BulkSMSBD's numeric response code from a JSON body, e.g.
     * {"response_code":202,"success_message":"..."}. Returns null if absent.
     */
    private function responseCode(string $body): ?int
    {
        $json = json_decode($body, true);

        if (is_array($json) && isset($json['response_code'])) {
            return (int) $json['response_code'];
        }

        return null;
    }

    /**
     * BulkSMSBD expects 88016XXXXXXXX format. Supports comma-separated lists so
     * one call can target multiple recipients.
     */
    private function normalize(string $number): string
    {
        $parts = array_filter(array_map('trim', explode(',', $number)));

        $normalized = array_map(function (string $n): string {
            $digits = preg_replace('/\D+/', '', $n) ?? '';

            if (str_starts_with($digits, '880')) {
                return $digits;
            }

            if (str_starts_with($digits, '0')) {
                return '88'.$digits;
            }

            return '880'.$digits;
        }, $parts);

        return implode(',', $normalized);
    }
}
