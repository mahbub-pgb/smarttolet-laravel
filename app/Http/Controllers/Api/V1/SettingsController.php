<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    /** GET /public/settings — secret-free settings for the frontend. */
    public function publicShow(): JsonResponse
    {
        return $this->ok($this->settings->publicView(), 'OK');
    }

    /** GET /admin/settings — masked admin view (shows which secrets are set). */
    public function adminIndex(): JsonResponse
    {
        return $this->ok($this->settings->adminView(), 'OK');
    }

    /** PUT /admin/settings — update settings; secrets only overwritten when provided. */
    public function adminUpdate(UpdateSettingsRequest $request): JsonResponse
    {
        $updated = $this->settings->update($request->validated());

        return $this->ok($updated, 'Settings updated.');
    }
}
