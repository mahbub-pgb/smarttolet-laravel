<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use App\Services\Sms\SmsManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    /** GET /admin/settings/maps — manage map zoom levels + browser key. */
    public function maps(): View
    {
        return view('admin.settings.maps', [
            'settings' => $this->settings->adminView(),
        ]);
    }

    /** POST /admin/settings/maps — persist map zoom levels + browser key. */
    public function updateMaps(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'map_default_zoom' => ['required', 'integer', 'between:0,22'],
            'map_pinned_zoom' => ['required', 'integer', 'between:0,22'],
            'map_default_lat' => ['required', 'numeric', 'between:-90,90'],
            'map_default_lng' => ['required', 'numeric', 'between:-180,180'],
            'google_maps_browser_key' => ['nullable', 'string', 'max:255'],
        ], [
            'map_default_zoom.between' => 'Zoom must be between 0 (whole world) and 22 (street level).',
            'map_pinned_zoom.between' => 'Zoom must be between 0 (whole world) and 22 (street level).',
        ]);

        $this->settings->update([
            'map_default_zoom' => (int) $data['map_default_zoom'],
            'map_pinned_zoom' => (int) $data['map_pinned_zoom'],
            'map_default_lat' => (float) $data['map_default_lat'],
            'map_default_lng' => (float) $data['map_default_lng'],
            'google_maps_browser_key' => $data['google_maps_browser_key'] ?? null,
        ]);

        return back()->with('status', 'Map settings saved.');
    }

    /** GET /admin/settings/sms — manage bulk SMS credentials. */
    public function sms(): View
    {
        return view('admin.settings.sms', [
            'settings' => $this->settings->adminView(),
        ]);
    }

    /** POST /admin/settings/sms — persist SMS provider + credentials. */
    public function updateSms(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sms_provider' => ['required', 'in:mock,bulksmsbd'],
            'sms_sender_id' => ['nullable', 'string', 'max:50'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
        ]);

        // Empty secret (api key) submissions are ignored by the service so an
        // existing key is never wiped by saving the form without re-entering it.
        $this->settings->update([
            'sms_provider' => $data['sms_provider'],
            'sms_sender_id' => $data['sms_sender_id'] ?? null,
            'sms_api_key' => $data['sms_api_key'] ?? null,
        ]);

        return back()->with('status', 'SMS settings saved.');
    }

    /** POST /admin/settings/sms/test — send a test SMS to a number. */
    public function testSms(Request $request, SmsManager $sms): RedirectResponse
    {
        $data = $request->validate([
            'test_number' => ['required', 'string', 'regex:/^01[0-9]{9}$/'],
        ], [
            'test_number.regex' => 'Enter a valid number, e.g. 01712345678.',
        ]);

        $ok = $sms->driver()->send(
            $data['test_number'],
            'SmartToLet test message — your SMS gateway is configured correctly.',
        );

        return back()->with(
            'status',
            $ok ? 'Test SMS dispatched to '.$data['test_number'].'.' : 'Test SMS failed — check the credentials and logs.',
        );
    }
}
