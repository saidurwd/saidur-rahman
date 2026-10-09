<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/** A tamper-proof "form rendered at" timestamp, used to catch submissions that arrive faster than a person can type. */
class FormToken
{
    public const MIN_SECONDS = 3;

    public const MAX_AGE_SECONDS = 86400;

    public function issue(): string
    {
        return Crypt::encryptString((string) now()->getTimestamp());
    }

    /** @return 'ok'|'too_fast'|'invalid' */
    public function check(?string $token): string
    {
        try {
            $issued = (int) Crypt::decryptString((string) $token);
        } catch (DecryptException) {
            return 'invalid';
        }

        $age = now()->getTimestamp() - $issued;

        return match (true) {
            $age < 0 || $age > self::MAX_AGE_SECONDS => 'invalid',
            $age < self::MIN_SECONDS => 'too_fast',
            default => 'ok',
        };
    }
}
