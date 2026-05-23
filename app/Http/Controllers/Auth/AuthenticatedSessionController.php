<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->two_factor_enabled) {
            return redirect()->intended(route('two-factor.setup', absolute: false));
        }

        if ($twoFactor->hasRememberedDevice($request, $user)) {
            $request->session()->put('two_factor_verified_at', now()->timestamp);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        $intended = $request->session()->pull('url.intended', route('dashboard', absolute: false));
        $rememberLogin = $request->boolean('remember');

        Auth::guard('web')->logout();

        $request->session()->regenerate();
        $request->session()->put('two_factor', [
            'user_id' => $user->id,
            'remember_login' => $rememberLogin,
            'intended' => $intended,
        ]);

        return redirect()->route('two-factor.challenge');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'two_factor',
            'two_factor_verified_at',
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
