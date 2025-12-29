<?php

namespace App\Casts;

use App\Support\EncryptionHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class EncryptedCastZuoraClientSecret implements CastsAttributes
{
    /**
     * Cast the given value (from database to application)
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Decrypt using shared helper
        return EncryptionHelper::decrypt($value);
    }

    /**
     * Prepare the given value for storage (from application to database)
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Encrypt using shared helper
        return EncryptionHelper::encrypt($value);
    }
}
