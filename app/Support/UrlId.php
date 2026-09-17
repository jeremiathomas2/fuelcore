<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Opaque, reversible identifiers for URLs.
 *
 * Database keys are encrypted and base64url-encoded so sequential ids are
 * never exposed in query strings or route parameters. Decoding is tolerant:
 * an invalid or tampered token simply resolves to null.
 */
class UrlId
{
    public static function encode(int|string|null $id): ?string
    {
        if ($id === null || $id === '' || ! is_numeric($id)) {
            return null;
        }

        $payload = Crypt::encryptString((string) (int) $id);

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    public static function decode(mixed $token): ?int
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $payload = base64_decode(strtr($token, '-_', '+/'), true);

        if ($payload === false) {
            return null;
        }

        try {
            $value = Crypt::decryptString($payload);
        } catch (Throwable) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
