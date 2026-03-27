<?php

namespace App\Http\Requests\StudyTracker;

use App\Models\PracticeLog;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePracticeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'practice_type'    => ['required', 'in:' . implode(',', array_keys(PracticeLog::$practiceTypes))],
            'details'          => ['required', 'string', 'max:5000'],
            'practiced_on'     => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'outcome'          => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'practice_type.required' => 'Practice type is required.',
            'details.required'       => 'Practice details are required.',
            'practiced_on.required'  => 'Practice date is required.',
        ];
    }
}
