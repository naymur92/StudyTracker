<?php

namespace App\Http\Requests\StudyTracker;

use App\Models\Topic;
use App\Services\IdHasher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMistakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'question' => [$required, 'string', 'max:500'],
            'correct_answer' => [$required, 'string', 'max:2000'],
            'cause' => [$required, Rule::in(Topic::MISTAKE_CAUSES)],
            'my_answer' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:200'],
            'parent_topic_id' => [
                'nullable',
                'integer',
                Rule::exists('topics', 'id')->where(fn ($q) => $q
                    ->where('user_id', auth()->id())
                    ->where('kind', Topic::KIND_TOPIC)
                    ->whereNull('deleted_at')),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q
                    ->where(fn ($w) => $w->where('user_id', auth()->id())->orWhereNull('user_id'))
                    ->whereNull('deleted_at')),
            ],
            'logged_on' => [$this->isMethod('post') ? 'nullable' : 'prohibited', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_topic_id.exists' => 'Choose one of your own topics as the parent.',
            'logged_on.before_or_equal' => 'A mistake cannot be logged in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['parent_topic_id', 'category_id'] as $key) {
            if ($this->filled($key)) {
                $this->merge([$key => IdHasher::decode((string) $this->input($key)) ?? -1]);
            }
        }
    }
}
