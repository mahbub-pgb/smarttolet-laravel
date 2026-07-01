<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Forgot-password recovery for the session (web) guard. Because accounts are
 * phone-first, recovery is verified by an SMS OTP — the same OtpService as
 * signup, under a distinct "password_reset" purpose (its own cooldown +
 * lockout). Route throttling adds a per-IP / per-number request cap on top.
 */
class PasswordResetController extends Controller
{
    private const PURPOSE = 'password_reset';

    public function __construct(private OtpService $otp) {}

    // --- Step 1: phone number --------------------------------------------

    /** GET /password/forgot */
    public function showForgot(): View
    {
        return view('auth.password-forgot');
    }

    /** POST /password/forgot — send a reset code to a registered number. */
    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Must belong to a real account — avoids texting arbitrary numbers.
            'mobile' => ['required', 'string', 'regex:/^01[0-9]{9}$/', 'exists:users,mobile'],
        ], [
            'mobile.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'mobile.exists' => 'No account is registered with this number.',
        ]);

        $mobile = $data['mobile'];

        try {
            $this->otp->request(self::PURPOSE, $mobile, $mobile);
        } catch (ApiException $e) {
            return back()->withInput()->withErrors(['mobile' => $e->getMessage()]);
        }

        $request->session()->put('pwreset_mobile', $mobile);
        $request->session()->forget('pwreset_verified');

        return redirect()->route('password.verify')
            ->with('status', 'We sent a reset code to '.$mobile.'.');
    }

    // --- Step 2: verify OTP ----------------------------------------------

    /** GET /password/verify */
    public function showVerify(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('pwreset_mobile')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.password-verify', ['mobile' => $request->session()->get('pwreset_mobile')]);
    }

    /** POST /password/verify */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('pwreset_mobile');

        if (! $mobile) {
            return redirect()->route('password.forgot');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'regex:/^[0-9]{4,8}$/'],
        ], [
            'code.regex' => 'Enter the numeric code we sent you.',
        ]);

        try {
            $this->otp->verify(self::PURPOSE, $mobile, $data['code']);
        } catch (ApiException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $request->session()->put('pwreset_verified', $mobile);

        return redirect()->route('password.reset');
    }

    /** POST /password/resend — respects the OTP cooldown. */
    public function resendOtp(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('pwreset_mobile');

        if (! $mobile) {
            return redirect()->route('password.forgot');
        }

        try {
            $this->otp->request(self::PURPOSE, $mobile, $mobile);
        } catch (ApiException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', 'A new code has been sent.');
    }

    // --- Step 3: set a new password --------------------------------------

    /** GET /password/reset */
    public function showReset(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('pwreset_verified')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.password-reset');
    }

    /** POST /password/reset */
    public function reset(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('pwreset_verified');

        if (! $mobile) {
            return redirect()->route('password.forgot');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('mobile', $mobile)->first();

        if (! $user) {
            $request->session()->forget(['pwreset_mobile', 'pwreset_verified']);

            return redirect()->route('password.forgot')
                ->withErrors(['mobile' => 'Account not found. Please try again.']);
        }

        // Set the new password and invalidate any existing API refresh tokens.
        $user->forceFill([
            'password' => $data['password'],
            'token_version' => $user->token_version + 1,
        ])->save();

        $request->session()->forget(['pwreset_mobile', 'pwreset_verified']);

        return redirect()->route('login')
            ->with('status', 'Your password has been reset. Please log in.');
    }
}
