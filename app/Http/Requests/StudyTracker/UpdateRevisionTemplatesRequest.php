<?php

namespace App\Http\Requests\StudyTracker;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRevisionTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'templates'                 => ['required', 'array', 'min:1', 'max:10'],
            'templates.*.sequence_no'   => ['required', 'integer', 'min:1', 'max:20', 'distinct'],
            'templates.*.day_offset'    => ['required', 'integer', 'min:1', 'max:3650', 'distinct'],
            'templates.*.name'          => ['nullable', 'string', 'max:100'],
            'templates.*.is_active'     => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'templates.required'               => 'At least one revision template is required.',
            'templates.array'                  => 'Templates must be an array.',
            'templates.*.sequence_no.required' => 'Each template needs sequence number.',
            'templates.*.sequence_no.distinct' => 'Sequence numbers must be unique.',
            'templates.*.day_offset.required'  => 'Each template needs day offset.',
            'templates.*.day_offset.distinct'  => 'Day offsets must be unique.',
        ];
    }
}
