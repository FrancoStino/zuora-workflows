<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateDomain implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $domain) {
            if (! $this->isValidDomain($domain)) {
                $fail("The domain '{$domain}' is not valid. Please use format: example.com");

                return;
            }
        }
    }

    /**
     * Validate a single domain.
     */
    private function isValidDomain(string $domain): bool
    {
        // Remove whitespace
        $domain = trim($domain);

        // Reject domains starting with www.
        if (stripos($domain, 'www.') === 0) {
            return false;
        }

        // Must contain at least one dot
        if (strpos($domain, '.') === false) {
            return false;
        }

        // RFC-compliant domain validation
        return (bool) preg_match(
            '/^(?:(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}|localhost)$/i',
            $domain
        );
    }
}
