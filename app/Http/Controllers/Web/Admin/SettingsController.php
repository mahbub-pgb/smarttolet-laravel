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
