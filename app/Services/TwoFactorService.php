<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RegisteredDevice;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

final class TwoFactorService
{
    public function __construct(
        private readonly Google2FA $google2fa,
    ) {}

    /**
     * Verify a TOTP code against the stored secret.
     */
    public function verify(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Verify a recovery code and consume it if valid.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        /** @var array<int, string> $codes */
        $codes = json_decode($user->two_factor_recovery_codes ?? '[]', true);

        foreach ($codes as $index => $hashedCode) {
            if (Hash::check($code, $hashedCode)) {
                unset($codes[$index]);
                $user->forceFill([
                    'two_factor_recovery_codes' => json_encode(array_values($codes)),
                ])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Check if the given cookie token belongs to a trusted device for this user.
     */
    public function isDeviceTrusted(User $user, string $token): bool
    {
        return RegisteredDevice::withoutGlobalScope(SchoolScope::class)
            ->where('user_id', $user->id)
            ->where('device_fingerprint', hash('sha256', $token))
            ->whereNotNull('trusted_at')
            ->exists();
    }

    /**
     * Record a trusted device, return plain token to store in cookie.
     */
    public function createTrustedDevice(User $user, string $userAgent, string $ip): string
    {
        $token = Str::random(64);

        RegisteredDevice::withoutGlobalScope(SchoolScope::class)->create([
            'school_id' => session('current_school_id'),
            'user_id' => $user->id,
            'device_name' => $this->guessDeviceName($userAgent),
            'device_fingerprint' => hash('sha256', $token),
            'last_seen_at' => now(),
            'trusted_at' => now(),
        ]);

        return $token;
    }

    private function guessDeviceName(string $userAgent): string
    {
        if (str_contains($userAgent, 'iPhone')) return 'iPhone';
        if (str_contains($userAgent, 'iPad')) return 'iPad';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';

        return 'Unknown Device';
    }
}
