<?php

namespace App\Http\Requests\StudyTracker;

use App\Services\IdHasher;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Topic;
use Illuminate\Validation\Rule;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:200'],
            'category_id'      => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($q) {
                    $q->where('user_id', auth()->id())->orWhereNull('user_id');
                }),
            ],
            'description'      => ['nullable', 'string', 'max:2000'],
            'source_link'      => ['nullable', 'url', 'max:500'],
            'difficulty'       => ['nullable', 'in:easy,medium,hard'],
            'first_study_date' => ['required', 'date'],
            'notes'            => ['nullable', 'string', 'max:5000'],
            'tags'             => ['nullable', 'array'],
            'tags.*'           => ['string', 'max:50'],
            'recall_questions'            => ['nullable', 'array', 'max:' . config('study.review.max_recall_questions', 10)],
            'recall_questions.*'          => ['array:question,answer'],
            'recall_questions.*.question' => ['required', 'string', 'max:500'],
            'recall_questions.*.answer'   => ['nullable', 'string', 'max:2000'],
            'summary'                     => ['nullable', 'string', 'max:2000'],
            'practice_prompt'             => ['nullable', 'string', 'max:500'],
            'lane'                        => ['nullable', Rule::in(Topic::LANES)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'            => 'Topic title is required.',
            'first_study_date.required' => 'Study start date is required.',
            'first_study_date.date'     => 'Invalid date format.',
            'source_link.url'           => 'Source link must be a valid URL.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->recall_questions)) {
            $this->merge([
                'recall_questions' => array_values(array_map(
                    fn ($item) => is_array($item)
                        ? ['question' => trim((string) ($item['question'] ?? '')), 'answer' => isset($item['answer']) && trim((string) $item['answer']) !== '' ? trim((string) $item['answer']) : null]
                        : $item,
                    $this->recall_questions
                )),
            ]);
        }

        if ($this->filled('category_id')) {
            $this->merge([
                'category_id' => IdHasher::decode((string) $this->category_id),
            ]);
        }
    }
}
