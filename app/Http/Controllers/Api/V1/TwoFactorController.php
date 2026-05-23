<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class TwoFactorController extends Controller
{
    public function setup(Request $request, TwoFactorAuthenticationService $twoFactor): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled.',
                'two_factor_enabled' => true,
                'remaining_recovery_codes' => count($user->two_factor_recovery_codes ?? []),
            ]);
        }

        if (! $user->two_factor_secret) {
            $user->forceFill([
                'two_factor_secret' => $twoFactor->generateSecret(),
            ])->save();
        }

        return response()->json([
            'message' => 'Scan the QR code or enter the manual key, then confirm with a 6-digit code.',
            'two_factor' => $this->setupPayload($user, $twoFactor),
        ]);
    }

    public function confirm(Request $request, TwoFactorAuthenticationService $twoFactor): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            throw ValidationException::withMessages([
                'code' => ['Start 2FA setup before confirming a code.'],
            ]);
        }

        if (! $twoFactor->verifyCode($user, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['The authenticator code is invalid or has expired.'],
            ]);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $twoFactor->hashRecoveryCodes($recoveryCodes),
        ])->save();

        $token = $this->createToken($user, $request, ['*']);
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Two-factor authentication enabled.',
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'recovery_codes' => $recoveryCodes,
            'user' => $this->userPayload($user),
        ]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Recovery codes cannot be viewed again after generation. Regenerate them if you need a fresh set.',
            'remaining_recovery_codes' => count($request->user()->two_factor_recovery_codes ?? []),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorAuthenticationService $twoFactor): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect.'],
            ]);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $twoFactor->hashRecoveryCodes($recoveryCodes),
        ])->save();

        return response()->json([
            'message' => 'New recovery codes generated. Old recovery codes no longer work.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request, TwoFactorAuthenticationService $twoFactor): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect.'],
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => $twoFactor->generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ])->save();

        DB::table('two_factor_remembered_devices')
            ->where('user_id', $user->id)
            ->delete();

        $token = $this->createToken($user, $request, ['2fa:setup']);

        $user->tokens()
            ->where('id', '!=', $token->accessToken->id)
            ->delete();

        return response()->json([
            'message' => 'Two-factor authentication disabled. Complete setup again before using protected features.',
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'requires_two_factor_setup' => true,
            'two_factor' => $this->setupPayload($user, $twoFactor),
        ]);
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function createToken(User $user, Request $request, array $abilities): NewAccessToken
    {
        return $user->createToken(
            $request->string('device_name')->toString() ?: 'api-client',
            $abilities
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function setupPayload(User $user, TwoFactorAuthenticationService $twoFactor): array
    {
        return [
            'manual_key' => $user->two_factor_secret,
            'otpauth_url' => $twoFactor->otpauthUrl($user),
            'qr_code_svg' => $twoFactor->qrCodeSvg($user),
            'compatible_apps' => [
                'Google Authenticator',
                'Microsoft Authenticator',
                'Authy',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'two_factor_enabled' => $user->two_factor_enabled,
            'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->toISOString(),
        ];
    }
}
