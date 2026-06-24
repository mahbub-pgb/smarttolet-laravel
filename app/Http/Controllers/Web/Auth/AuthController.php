<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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

    /** GET /register */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /** POST /register — create a user account and sign in. */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'role' => Role::User,
        ]);

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Welcome to SmartToLet!');
    }

    /** POST /logout */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** GET /dashboard — the signed-in user's listings. */
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $listings = $user->listings()->latest()->paginate(10);

        return view('dashboard', compact('user', 'listings'));
    }
}
