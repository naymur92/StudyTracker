<?php

namespace App\Http\Requests\StudyTracker;

use App\Services\StudyTracker\DataDeletion\DataCategoryRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDataDeletionRequest extends FormRequest
{
    public const CONFIRMATION_WORD = 'DELETE';

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', 'distinct', Rule::in(app(DataCategoryRegistry::class)->keys())],
            'reason' => ['nullable', 'string', 'max:1000'],
            'confirmation' => ['required', 'string', Rule::in([self::CONFIRMATION_WORD])],
        ];
    }

    public function messages(): array
    {
        return [
            'categories.required' => 'Choose at least one kind of data to delete.',
            'categories.min' => 'Choose at least one kind of data to delete.',
            'categories.*.in' => 'One of the chosen data categories is not recognised.',
            'categories.*.distinct' => 'Each data category can be chosen only once.',
            'confirmation.required' => 'Type DELETE to confirm.',
            'confirmation.in' => 'Type DELETE to confirm.',
        ];
    }
}
