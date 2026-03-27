<?php

namespace App\Http\Requests\StudyTracker;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'notes'               => ['nullable', 'string', 'max:2000'],
            'difficulty_feedback' => ['nullable', 'in:easy,medium,hard'],
        ];
    }
}
