<?php

namespace App\Http\Requests\StudyTracker;

use App\Services\IdHasher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:200'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($q) {
                    $q->where('user_id', auth()->id())->orWhereNull('user_id');
                }),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'source_link' => ['nullable', 'url', 'max:500'],
            'difficulty'  => ['nullable', 'in:easy,medium,hard'],
            'status'      => ['nullable', 'in:active,completed,archived'],
            'notes'       => ['nullable', 'string', 'max:5000'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
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
