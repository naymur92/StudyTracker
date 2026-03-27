<?php

namespace App\Http\Requests\StudyTracker;

use App\Services\IdHasher;
use Illuminate\Foundation\Http\FormRequest;

class IndexTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'search'      => ['nullable', 'string', 'max:200'],
            'status'      => ['nullable', 'in:active,completed,archived'],
            'category_id' => ['nullable'],
            'difficulty'  => ['nullable', 'in:easy,medium,hard'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'        => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Decode the hashed category_id filter before validation runs.
     * An invalid or absent hash leaves category_id as null (no filter applied).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('category_id')) {
            $this->merge([
                'category_id' => IdHasher::decode((string) $this->category_id),
            ]);
        }
    }
}
