<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationService
{
    public const REMEMBER_DEVICE_COOKIE = 'ariatyx_2fa_device';

    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
        $this->google2fa->setWindow(1);
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function qrCodeSvg(User $user): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(260, 2),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($this->otpauthUrl($user));
    }

    public function otpauthUrl(User $user): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name', 'Ariatyx Gaming'),
            $user->email,
            $user->two_factor_secret
        );
    }

    public function verifyCode(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        return $this->google2fa->verifyKey($user->two_factor_secret, $code, 1) !== false;
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return collect($codes)
            ->map(fn (string $code) => Hash::make($this->normalizeRecoveryCode($code)))
            ->all();
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $normalizedCode = $this->normalizeRecoveryCode($code);
        $hashes = $user->two_factor_recovery_codes ?? [];

        foreach ($hashes as $index => $hash) {
            if (Hash::check($normalizedCode, $hash)) {
                unset($hashes[$index]);

                $user->forceFill([
                    'two_factor_recovery_codes' => array_values($hashes),
                ])->save();

                return true;
            }
        }

        return false;
    }

    public function rememberDevice(User $user): void
    {
        $token = Str::random(64);

        DB::table('two_factor_remembered_devices')->insert([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Cookie::queue(cookie(
            self::REMEMBER_DEVICE_COOKIE,
            $user->id.'|'.$token,
            60 * 24 * 30,
            '/',
            config('session.domain'),
            (bool) config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        ));
    }

    public function hasRememberedDevice(Request $request, User $user): bool
    {
        $cookie = $request->cookie(self::REMEMBER_DEVICE_COOKIE);

        if (! is_string($cookie) || ! str_contains($cookie, '|')) {
            return false;
        }

        [$userId, $token] = explode('|', $cookie, 2);

        if ((int) $userId !== $user->id || $token === '') {
            return false;
        }

        $device = DB::table('two_factor_remembered_devices')
            ->where('user_id', $user->id)
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        if (! $device) {
            return false;
        }

        DB::table('two_factor_remembered_devices')
            ->where('id', $device->id)
            ->update([
                'last_used_at' => now(),
                'updated_at' => now(),
            ]);

        return true;
    }

    public function forgetRememberedDeviceCookie(): void
    {
        Cookie::queue(Cookie::forget(
            self::REMEMBER_DEVICE_COOKIE,
            '/',
            config('session.domain')
        ));
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return Str::upper(str_replace(' ', '', trim($code)));
    }
}
