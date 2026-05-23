<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TwoFactorDisableController extends Controller
{
    public function show(): View
    {
        return view('auth.two-factor-disable');
    }

    public function destroy(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ])->save();

        DB::table('two_factor_remembered_devices')
            ->where('user_id', $user->id)
            ->delete();

        $twoFactor->forgetRememberedDeviceCookie();

        $request->session()->forget('two_factor_verified_at');

        return redirect()
            ->route('two-factor.setup')
            ->with('status', 'Two-factor authentication was disabled. Set it up again to continue using Ariatyx Gaming.');
    }
}
