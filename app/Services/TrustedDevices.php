<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * "Trust this browser" for two-factor sign-in. After a correct code the admin can ask to skip the prompt on this
 * browser for a while. The cookie is signed with the app key and is tied to the account's secret, password, enrolment
 * time and a revocation code, so it stops working if any of them change or when the admin uses "Forget trusted browsers".
 */
class TrustedDevices
{
    public const COOKIE = 'admin_trusted_device';

    private const NONCE = 'two_factor_trust_nonce';

    public function __construct(private SettingService $settings) {}

    public function cookie(User $user): Cookie
    {
        $expires = now()->addDays((int) config('admin.trust_days'))->getTimestamp();

        return Cookie::create(self::COOKIE, $this->value($user, $expires), $expires, '/admin', null, $this->secure(), true, false, Cookie::SAMESITE_LAX);
    }

    public function forgetCookie(): Cookie
    {
        return Cookie::create(self::COOKIE, '', 1, '/admin', null, $this->secure(), true, false, Cookie::SAMESITE_LAX);
    }

    public function isTrusted(Request $request, User $user): bool
    {
        $parts = explode('|', (string) $request->cookie(self::COOKIE));

        if (count($parts) !== 3 || (int) $parts[0] !== $user->id || (int) $parts[1] < now()->getTimestamp()) {
            return false;
        }

        return hash_equals($this->value($user, (int) $parts[1]), implode('|', $parts));
    }

    /** Makes every trusted browser stop being trusted, by changing what the signature is built from. */
    public function forgetAll(): void
    {
        $this->settings->set(self::NONCE, Str::random(32), 'security');
    }

    private function value(User $user, int $expires): string
    {
        $fingerprint = hash('sha256', $user->getRawOriginal('two_factor_secret').'|'.$user->password.'|'.$user->getAttribute('two_factor_confirmed_at')?->getTimestamp().'|'.$this->settings->get(self::NONCE));

        return $user->id.'|'.$expires.'|'.hash_hmac('sha256', $user->id.'|'.$expires.'|'.$fingerprint, (string) config('app.key'));
    }

    private function secure(): bool
    {
        return (bool) config('session.secure');
    }
}
