<?php

namespace App\Http\Requests\StudyTracker;

use App\Models\StudyWeek;
use App\Services\StudyTracker\StudyPreferences;
use App\Services\StudyTracker\WeeklyPlanService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudyWeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'week_start' => ['required', 'date_format:Y-m-d'],
            'gear' => ['required', Rule::in(StudyWeek::GEARS)],
            'major_focus' => ['nullable', 'string', 'max:200'],
            'minor_focus' => ['nullable', 'string', 'max:200'],
            'generate_blocks' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $start = Carbon::parse($this->input('week_start'));
                $weekStartsOn = StudyPreferences::for($this->user())->weekStartsOn();

                if ($start->dayOfWeek !== $weekStartsOn) {
                    $validator->errors()->add('week_start', 'The week must start on '.['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$weekStartsOn].' (your week-start setting).');

                    return;
                }

                if (app(WeeklyPlanService::class)->overlaps($this->user()->id, $start)) {
                    $validator->errors()->add('week_start', 'You already have a plan for this week.');
                }
            },
        ];
    }
}
