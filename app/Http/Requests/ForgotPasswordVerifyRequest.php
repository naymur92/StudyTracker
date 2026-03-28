<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'                 => ['required', 'email:rfc,dns'],
            'code'                  => ['required', 'digits:6'],
            'password'              => ['required', new StrongPassword()],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'                 => 'Email is required.',
            'email.email'                    => 'Invalid email format.',
            'code.required'                  => 'Verification code is required.',
            'code.digits'                    => 'Verification code must be 6 digits.',
            'password.required'              => 'Password is required.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.same'     => 'Password confirmation does not match.',
        ];
    }
}
