<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DisposableEmail implements ValidationRule
{
    /**
     * Common disposable email domains blocked for registration.
     * Keep list concise and maintainable; extend as needed.
     *
     * @var array<int, string>
     */
    private array $blockedDomains = [
        'mailinator.com',
        'guerrillamail.com',
        '10minutemail.com',
        'temp-mail.org',
        'tempmail.com',
        'yopmail.com',
        'trashmail.com',
        'dispostable.com',
        'fakeinbox.com',
        'sharklasers.com',
    ];

    /**
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '@')) {
            $fail('Invalid email address format.');
            return;
        }

        $domain = strtolower(substr(strrchr($value, '@'), 1) ?: '');

        if ($domain === '' || in_array($domain, $this->blockedDomains, true)) {
            $fail('Disposable email addresses are not allowed.');
        }
    }
}
