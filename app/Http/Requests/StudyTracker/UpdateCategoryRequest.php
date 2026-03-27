<?php

namespace App\Http\Requests\StudyTracker;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'icon'  => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'color.regex'   => 'Color must be a valid hex code (e.g. #4e73df).',
        ];
    }
}
