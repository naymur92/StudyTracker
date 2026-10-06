<?php

namespace App\Http\Requests\StudyTracker;

use App\Services\IdHasher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Start a block's timer, optionally setting its topic and minutes first. */
class StartBlockTimerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'topic_id' => [
                'nullable', 'integer',
                Rule::exists('topics', 'id')->where(fn ($q) => $q->where('user_id', auth()->id())->whereNull('deleted_at')),
            ],
            'planned_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('topic_id')) {
            $this->merge(['topic_id' => IdHasher::decode((string) $this->input('topic_id')) ?? -1]);
        }
    }
}
