<?php

namespace App\Http\Requests\StudyTracker;

use App\Models\StudyWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudyWeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'gear' => ['sometimes', Rule::in(StudyWeek::GEARS)],
            'major_focus' => ['nullable', 'string', 'max:200'],
            'minor_focus' => ['nullable', 'string', 'max:200'],
            'reflection' => ['nullable', 'string', 'max:2000'],
            'if_then_plan' => ['nullable', 'string', 'max:500'],
            'output_note' => ['nullable', 'string', 'max:300'],
        ];
    }
}
