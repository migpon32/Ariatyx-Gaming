<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorSetupController extends Controller
{
    public function show(Request $request, TwoFactorAuthenticationService $twoFactor): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return redirect()->route('two-factor.recovery-codes');
        }

        if (! $user->two_factor_secret) {
            $user->forceFill([
                'two_factor_secret' => $twoFactor->generateSecret(),
            ])->save();
        }

        return view('auth.two-factor-setup', [
            'qrCodeSvg' => $twoFactor->qrCodeSvg($user),
            'secret' => $user->two_factor_secret,
        ]);
    }

    public function confirm(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $this->ensureIsNotRateLimited($request);

        $user = $request->user();

        if (! $twoFactor->verifyCode($user, $request->string('code')->toString())) {
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                'code' => 'The authenticator code is invalid or has expired.',
            ]);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $twoFactor->hashRecoveryCodes($recoveryCodes),
        ])->save();

        RateLimiter::clear($this->throttleKey($request));

        $request->session()->put('two_factor_verified_at', now()->timestamp);

        return redirect()
            ->route('two-factor.recovery-codes')
            ->with('recovery_codes', $recoveryCodes)
            ->with('status', 'Two-factor authentication is now enabled.');
    }

    public function recoveryCodes(Request $request): View
    {
        return view('auth.two-factor-recovery-codes', [
            'recoveryCodes' => session('recovery_codes', []),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        $request->user()->forceFill([
            'two_factor_recovery_codes' => $twoFactor->hashRecoveryCodes($recoveryCodes),
        ])->save();

        return redirect()
            ->route('two-factor.recovery-codes')
            ->with('recovery_codes', $recoveryCodes)
            ->with('status', 'New recovery codes generated. Your old codes no longer work.');
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
        return 'two-factor-setup:'.$request->user()->id.'|'.$request->ip();
    }
}
