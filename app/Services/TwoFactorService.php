<?php

namespace App\Services;

use App\Models\User;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(private Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function provisioningUri(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);
    }

    /** Inline SVG of the QR code, rendered locally so the secret never leaves the server. */
    public function qrSvg(User $user, string $secret): string
    {
        $svg = (new QRCode(new QROptions([
            'outputType' => QRMarkupSVG::class,
            'outputBase64' => false,
            'addQuietzone' => true,
            'svgPreserveAspectRatio' => 'xMidYMid meet',
        ])))->render($this->provisioningUri($user, $secret));

        return (string) preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg);
    }

    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        return strlen($code) === 6 && $this->google2fa->verifyKey($secret, $code, 1);
    }

    /** @return list<string> */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))
            ->all();
    }

    /** Consumes a recovery code if it matches. Returns whether it did. */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::lower(trim($code));
        /** @var list<string> $codes */
        $codes = $user->two_factor_recovery_codes ? (array) json_decode($user->two_factor_recovery_codes, true) : [];

        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => json_encode(array_values(array_diff($codes, [$code]))),
        ])->save();

        return true;
    }
}
