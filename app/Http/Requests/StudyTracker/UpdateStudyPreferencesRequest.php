<?php

namespace App\Http\Requests\StudyTracker;

use App\Services\StudyTracker\StudyPreferences;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudyPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'review_budget_minutes' => ['sometimes', 'integer', 'min:5', 'max:180'],
            'review_debt_threshold_minutes' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'minutes_per_review' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'weekly_new_topic_cap' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'week_starts_on' => ['sometimes', 'integer', 'min:0', 'max:6'],
            'off_days' => ['sometimes', 'array', 'max:6'],
            'off_days.*' => ['integer', 'min:0', 'max:6', 'distinct'],
            'success_threshold_percent' => ['sometimes', 'integer', 'min:50', 'max:100'],
            'study_profile' => ['sometimes', 'string', Rule::in(config('study.study_profiles'))],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                // Compare against the merged result so a partial update is checked correctly.
                $merged = array_merge(
                    StudyPreferences::for($this->user())->all(),
                    $this->validated()
                );

                if ((int) $merged['review_debt_threshold_minutes'] < (int) $merged['review_budget_minutes']) {
                    $validator->errors()->add(
                        'review_debt_threshold_minutes',
                        'The review debt threshold cannot be lower than the review budget.'
                    );
                }
            },
        ];
    }
}
