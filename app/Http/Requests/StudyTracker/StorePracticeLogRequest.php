<?php

namespace App\Http\Requests\StudyTracker;

use App\Models\PracticeLog;
use App\Services\IdHasher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePracticeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'topic_id'         => [
                'required',
                'integer',
                Rule::exists('topics', 'id')->where('user_id', auth()->id()),
            ],
            'task_id'          => [
                'nullable',
                'integer',
                Rule::exists('study_tasks', 'id')->where('user_id', auth()->id()),
            ],
            'practiced_on'     => ['required', 'date'],
            'practice_type'    => ['required', 'in:' . implode(',', array_keys(PracticeLog::$practiceTypes))],
            'details'          => ['required', 'string', 'max:5000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'outcome'          => ['nullable', 'string', 'max:300'],
        ];
    }

    public function messages(): array
    {
        return [
            'topic_id.required'      => 'Please select a topic.',
            'topic_id.exists'        => 'Selected topic does not exist.',
            'practiced_on.required'  => 'Practice date is required.',
            'practice_type.required' => 'Practice type is required.',
            'details.required'       => 'Practice details are required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('topic_id')) {
            $this->merge([
                'topic_id' => IdHasher::decode((string) $this->topic_id),
            ]);
        }

        if ($this->filled('task_id')) {
            $this->merge([
                'task_id' => IdHasher::decode((string) $this->task_id),
            ]);
        }
    }
}
