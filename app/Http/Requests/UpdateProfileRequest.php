<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:5', 'max:255'],
            'email' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.string'   => 'Invalid name.',
            'name.min'      => 'Minimum length is 5.',
            'name.max'      => 'Name length must be under 255 characters.',
            'email.prohibited' => 'Email cannot be changed from profile update.',
        ];
    }
}
