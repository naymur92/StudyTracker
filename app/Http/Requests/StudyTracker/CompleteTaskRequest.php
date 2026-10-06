<?php

namespace App\Http\Requests\StudyTracker;

use App\Models\StudyTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'difficulty_feedback' => ['nullable', 'in:easy,medium,hard'], // deprecated, use recall_grade
            'recall_grade'        => ['nullable', 'string', Rule::in(StudyTask::GRADES)],
            'review_seconds'      => ['nullable', 'integer', 'min:1', 'max:' . config('study.review.max_review_seconds', 3600)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $task = $this->route('task');

                if ($this->filled('recall_grade') && $task instanceof StudyTask && $task->task_type !== 'revision') {
                    $validator->errors()->add('recall_grade', 'A recall grade can only be given for revision tasks.');
                }
            },
        ];
    }
}
