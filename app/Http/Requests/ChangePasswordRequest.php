<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'current_password'],
            'new_password'          => ['required', new StrongPassword(), 'different:current_password'],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required.',
            'current_password.current_password' => 'Current password is incorrect.',
            'new_password.required' => 'New password is required.',
            'new_password.different' => 'New password must be different from current password.',
            'new_password_confirmation.required' => 'New password confirmation is required.',
            'new_password_confirmation.same' => 'New password confirmation does not match.',
        ];
    }
}
