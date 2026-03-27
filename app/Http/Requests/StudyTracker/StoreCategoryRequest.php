<?php

namespace App\Http\Requests\StudyTracker;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'  => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->where('user_id', auth()->id()),
            ],
            'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'icon'  => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.unique'   => 'You already have a category with this name.',
            'color.regex'   => 'Color must be a valid hex code (e.g. #4e73df).',
        ];
    }
}
