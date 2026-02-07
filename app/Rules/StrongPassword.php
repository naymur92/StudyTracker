<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check minimum length (8 characters)
        if (strlen($value) < 8) {
            $fail('The :attribute must be at least 8 characters long.');
            return;
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            $fail('The :attribute must contain at least one uppercase letter.');
            return;
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            $fail('The :attribute must contain at least one lowercase letter.');
            return;
        }

        // Check for at least one number
        if (!preg_match('/[0-9]/', $value)) {
            $fail('The :attribute must contain at least one number.');
            return;
        }

        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\/`~;]/', $value)) {
            $fail('The :attribute must contain at least one special character (!@#$%^&*(),.?":{}|<>_-+=[]\/`~;).');
            return;
        }

        // Check for no spaces
        if (preg_match('/\s/', $value)) {
            $fail('The :attribute must not contain spaces.');
            return;
        }
    }
}
