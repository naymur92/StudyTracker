<?php

namespace App\Http\Requests\StudyTracker;

use App\Models\PracticeLog;
use App\Services\IdHasher;
use Illuminate\Foundation\Http\FormRequest;

class IndexPracticeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'topic_id'      => ['nullable'],
            'practice_type' => ['nullable', 'in:' . implode(',', array_keys(PracticeLog::$practiceTypes))],
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'          => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Decode the hashed topic_id filter before validation runs.
     * An invalid or absent hash leaves topic_id as null (no filter applied).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('topic_id')) {
            $this->merge([
                'topic_id' => IdHasher::decode((string) $this->topic_id),
            ]);
        }
    }
}
