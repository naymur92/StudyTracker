<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterApiRequest extends FormRequest
{
    // Authorization: control who can make this request
    public function authorize(): bool
    {
        // Example: only logged-in users can create
        // return auth()->check();
        return true;
    }

    // Validation rules
    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'min:5'],
            'email'                 => ['required', 'email', 'unique:users,email'],
            'password'              => ['required', new StrongPassword()],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                     => 'Name is required.',
            'name.string'                       => 'Invalid Name.',
            'name.min'                          => 'Minimum length is 5.',
            'email.required'                    => 'Email is required.',
            'email.email'                       => 'Invalid Email.',
            'email.unique'                      => 'This Email is used.',
            'password.required'                 => 'Password is required.',
            'password_confirmation.required'    => 'Password Confirmation is required.',
            'password_confirmation.same'        => 'Password not matched.',
        ];
    }
}
