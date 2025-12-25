<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class EncryptionHelper
{
    /**
     * Encrypt a string value
     */
    public static function encrypt(mixed $value): ?string
    {
        // If value is null or empty, return null
        if (is_null($value) || $value === '') {
            return null;
        }

        // Encrypt the value
        return Crypt::encryptString((string) $value);
    }

    /**
     * Decrypt a string value
     * Handles backward compatibility with plain text values
     */
    public static function decrypt(mixed $value): ?string
    {
        // If value is null or empty, return null
        if (is_null($value) || $value === '') {
            return null;
        }

        try {
            // Try to decrypt - if it works, value was already encrypted
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // If decryption fails, assume it's plain text (migration scenario)
            // Return as is for backward compatibility
            return $value;
        }
    }
}
