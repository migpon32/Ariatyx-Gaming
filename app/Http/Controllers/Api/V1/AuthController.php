<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPasswordPattern;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, TwoFactorAuthenticationService $twoFactor): JsonResponse
    {
        $uppercaseAnswer = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== mb_strtoupper((string) $value, 'UTF-8')) {
                $fail('Security answers must be entered in CAPITAL LETTERS only.');
            }
        };

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults(), new StrongPasswordPattern()],
            'device_name' => ['nullable', 'string', 'max:255'],
            'security_question_1' => ['required', 'string', 'max:255'],
            'security_answer_1' => ['required', 'string', 'max:255', $uppercaseAnswer],
            'security_question_2' => ['required', 'string', 'max:255'],
            'security_answer_2' => ['required', 'string', 'max:255', $uppercaseAnswer],
            'security_question_3' => ['required', 'string', 'max:255'],
            'security_answer_3' => ['required', 'string', 'max:255', $uppercaseAnswer],
        ]);

        $user = User::create([
            'name' => $validated['username'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'security_question_1' => trim($validated['security_question_1']),
            'security_answer_1' => Hash::make(trim($validated['security_answer_1'])),
            'security_question_2' => trim($validated['security_question_2']),
            'security_answer_2' => Hash::make(trim($validated['security_answer_2'])),
            'security_question_3' => trim($validated['security_question_3']),
            'security_answer_3' => Hash::make(trim($validated['security_answer_3'])),
            'two_factor_secret' => $twoFactor->generateSecret(),
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'Account created. Complete 2FA setup before using protected features.',
            'token_type' => 'Bearer',
            'access_token' => $this->createToken($user, $request, ['2fa:setup']),
            'requires_two_factor_setup' => true,
            'user' => $this->userPayload($user),
            'two_factor' => $this->setupPayload($user, $twoFactor),
        ], 201);
    }

    public function login(Request $request, TwoFactorAuthenticationService $twoFactor): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        if (! $user->two_factor_enabled) {
            if (! $user->two_factor_secret) {
                $user->forceFill([
                    'two_factor_secret' => $twoFactor->generateSecret(),
                ])->save();
            }

            return response()->json([
                'message' => 'Password accepted. Complete 2FA setup before using protected features.',
                'token_type' => 'Bearer',
                'access_token' => $this->createToken($user, $request, ['2fa:setup']),
                'requires_two_factor_setup' => true,
                'user' => $this->userPayload($user),
                'two_factor' => $this->setupPayload($user, $twoFactor),
            ]);
        }

        $challengeToken = bin2hex(random_bytes(32));

        Cache::put($this->challengeKey($challengeToken), [
            'user_id' => $user->id,
            'device_name' => $validated['device_name'] ?? 'api-client',
        ], now()->addMinutes(10));

        return response()->json([
            'message' => 'Password accepted. Two-factor verification is required.',
            'requires_two_factor' => true,
            'challenge_token' => $challengeToken,
            'expires_in_seconds' => 600,
        ]);
    }

    public function verifyTwoFactorLogin(Request $request, TwoFactorAuthenticationService $twoFactor): JsonResponse
    {
        $validated = $request->validate([
            'challenge_token' => ['required', 'string', 'size:64'],
            'code' => ['nullable', 'required_without:recovery_code', 'digits:6'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:32'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $challenge = Cache::pull($this->challengeKey($validated['challenge_token']));

        if (! $challenge) {
            throw ValidationException::withMessages([
                'challenge_token' => ['The 2FA challenge is invalid or has expired.'],
            ]);
        }

        $user = User::find($challenge['user_id']);

        if (! $user || ! $user->two_factor_enabled) {
            throw ValidationException::withMessages([
                'challenge_token' => ['The 2FA challenge is invalid.'],
            ]);
        }

        $verified = isset($validated['code'])
            ? $twoFactor->verifyCode($user, $validated['code'])
            : $twoFactor->consumeRecoveryCode($user, $validated['recovery_code']);

        if (! $verified) {
            Cache::put($this->challengeKey($validated['challenge_token']), $challenge, now()->addMinutes(10));

            throw ValidationException::withMessages([
                isset($validated['code']) ? 'code' : 'recovery_code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        return response()->json([
            'message' => 'Login complete.',
            'token_type' => 'Bearer',
            'access_token' => $this->createToken($user, $request, ['*'], $challenge['device_name'] ?? null),
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function createToken(User $user, Request $request, array $abilities, ?string $deviceName = null): string
    {
        $name = $deviceName ?: $request->string('device_name')->toString();

        return $user->createToken(
            $name ?: 'api-client',
            $abilities
        )->plainTextToken;
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

    private function challengeKey(string $token): string
    {
        return 'api:two-factor-challenge:'.hash('sha256', $token);
    }
}
