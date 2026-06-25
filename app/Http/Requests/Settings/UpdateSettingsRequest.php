<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::ManageSettings) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['sometimes', 'string', 'max:120'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'support_email' => ['sometimes', 'nullable', 'email'],
            'support_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'maintenance_mode' => ['sometimes', 'boolean'],

            'google_maps_browser_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'google_maps_server_key' => ['sometimes', 'nullable', 'string', 'max:255'],

            'map_default_zoom' => ['sometimes', 'integer', 'between:0,22'],
            'map_pinned_zoom' => ['sometimes', 'integer', 'between:0,22'],
            'map_default_lat' => ['sometimes', 'numeric', 'between:-90,90'],
            'map_default_lng' => ['sometimes', 'numeric', 'between:-180,180'],

            'sms_provider' => ['sometimes', 'in:mock,bulksmsbd'],
            'sms_sender_id' => ['sometimes', 'nullable', 'string', 'max:30'],
            'sms_api_key' => ['sometimes', 'nullable', 'string', 'max:255'],

            'cloudinary_cloud_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cloudinary_api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cloudinary_api_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
