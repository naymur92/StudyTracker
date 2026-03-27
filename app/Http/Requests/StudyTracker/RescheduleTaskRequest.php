<?php

namespace App\Http\Requests\StudyTracker;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.required'       => 'New scheduled date is required.',
            'scheduled_date.date'           => 'Invalid date format.',
            'scheduled_date.after_or_equal' => 'Cannot reschedule to a past date.',
        ];
    }
}
