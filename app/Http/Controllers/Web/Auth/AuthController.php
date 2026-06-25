<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Enums\Role;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** OTP purpose used for the phone-verification signup flow. */
    private const PURPOSE = 'register';

    public function __construct(private OtpService $otp) {}

    // =====================================================================
    // Login / logout
    // =====================================================================

    /** GET /login */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /** POST /login — authenticate by mobile or email + password. */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        $credentials = [
            $field => $data['login'],
            'password' => $data['password'],
        ];

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => 'These credentials do not match our records.',
            ]);
        }

        if (Auth::guard('web')->user()?->is_suspended) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'login' => 'This account has been suspended.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** POST /logout */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // =====================================================================
    // Registration — step 1: phone number
    // =====================================================================

    /** GET /register — enter phone number. */
    public function showRegisterPhone(): View
    {
        return view('auth.register-phone');
    }

    /** POST /register — send an OTP to the phone via SMS. */
    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'regex:/^01[0-9]{9}$/', 'unique:users,mobile'],
        ], [
            'mobile.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'mobile.unique' => 'This number is already registered. Please log in instead.',
        ]);

        $mobile = $data['mobile'];

        try {
            $this->otp->request(self::PURPOSE, $mobile, $mobile);
        } catch (ApiException $e) {
            return back()->withInput()->withErrors(['mobile' => $e->getMessage()]);
        }

        // Remember the number awaiting verification (not yet verified).
        $request->session()->put('reg_mobile', $mobile);
        $request->session()->forget('reg_verified');

        return redirect()->route('register.verify')
            ->with('status', 'We sent a verification code to '.$mobile.'.');
    }

    // =====================================================================
    // Registration — step 2: verify OTP
    // =====================================================================

    /** GET /register/verify — enter the OTP. */
    public function showVerify(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('reg_mobile')) {
            return redirect()->route('register');
        }

        return view('auth.register-verify', ['mobile' => $request->session()->get('reg_mobile')]);
    }

    /** POST /register/verify — check the submitted code. */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('reg_mobile');

        if (! $mobile) {
            return redirect()->route('register');
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

        // Mark this number as verified and ready for account creation.
        $request->session()->put('reg_verified', $mobile);

        return redirect()->route('register.complete');
    }

    /** POST /register/resend — request a fresh OTP (respects the cooldown). */
    public function resendOtp(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('reg_mobile');

        if (! $mobile) {
            return redirect()->route('register');
        }

        try {
            $this->otp->request(self::PURPOSE, $mobile, $mobile);
        } catch (ApiException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', 'A new code has been sent.');
    }

    // =====================================================================
    // Registration — step 3: name, email, password
    // =====================================================================

    /** GET /register/complete — finish the profile. */
    public function showComplete(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has('reg_verified')) {
            return redirect()->route('register');
        }

        return view('auth.register-complete', ['mobile' => $request->session()->get('reg_verified')]);
    }

    /** POST /register/complete — create the account and sign in. */
    public function complete(Request $request): RedirectResponse
    {
        $mobile = $request->session()->get('reg_verified');

        if (! $mobile) {
            return redirect()->route('register');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'mobile' => $mobile,
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'role' => Role::User,
            'is_phone_verified' => true,
        ]);

        $request->session()->forget(['reg_mobile', 'reg_verified']);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Welcome to SmartToLet, '.$user->name.'!');
    }
}
