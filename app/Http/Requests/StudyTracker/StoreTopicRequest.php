<?php

namespace App\Http\Requests\StudyTracker;

use App\Services\IdHasher;
use Illuminate\Foundation\Http\FormRequest;
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
        if ($this->filled('category_id')) {
            $this->merge([
                'category_id' => IdHasher::decode((string) $this->category_id),
            ]);
        }
    }
}
