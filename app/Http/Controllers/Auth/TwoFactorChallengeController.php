<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'required_without:recovery_code', 'digits:6'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:32'],
            'remember_device' => ['nullable', 'boolean'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = User::find($request->session()->get('two_factor.user_id'));

        if (! $user || ! $user->two_factor_enabled) {
            $request->session()->forget('two_factor');

            return redirect()->route('login');
        }

        $verified = $request->filled('code')
            ? $twoFactor->verifyCode($user, $request->string('code')->toString())
            : $twoFactor->consumeRecoveryCode($user, $request->string('recovery_code')->toString());

        if (! $verified) {
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                $request->filled('code') ? 'code' : 'recovery_code' => 'The verification code is invalid or has expired.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $rememberLogin = (bool) $request->session()->pull('two_factor.remember_login', false);
        $intended = $request->session()->pull('two_factor.intended', route('dashboard', absolute: false));

        Auth::guard('web')->login($user, $rememberLogin);

        $request->session()->regenerate();
        $request->session()->forget('two_factor');
        $request->session()->put('two_factor_verified_at', now()->timestamp);

        if ($request->boolean('remember_device')) {
            $twoFactor->rememberDevice($user);
        }

        return redirect()->intended($intended);
    }

    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'code' => "Too many attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return 'two-factor-challenge:'.$request->session()->get('two_factor.user_id', 'missing').'|'.$request->ip();
    }
}
