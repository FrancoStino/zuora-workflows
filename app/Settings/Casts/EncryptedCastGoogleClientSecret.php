<?php

namespace App\Settings\Casts;

use App\Support\EncryptionHelper;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class EncryptedCastGoogleClientSecret implements SettingsCast
{
    /**
     * Get the value from the payload (decrypt from database)
     *
     * @param  mixed  $payload
     */
    public function get($payload): mixed
    {
        // If payload is null or empty, return empty string
        if (empty($payload)) {
            return '';
        }

        // Decrypt using shared helper
        return EncryptionHelper::decrypt($payload) ?? '';
    }

    /**
     * Set the value in the payload (encrypt for database)
     *
     * @param  mixed  $payload
     */
    public function set($payload): mixed
    {
        // If payload is null or empty, return empty string
        if (empty($payload)) {
            return '';
        }

        // Encrypt using shared helper
        return EncryptionHelper::encrypt($payload) ?? '';
    }
}
