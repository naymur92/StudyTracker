<?php

namespace App\Http\Requests\StudyTracker;

use Illuminate\Foundation\Http\FormRequest;

class QueueEmailedReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'months' => ['required', 'array', 'min:1', 'max:12'],
            'months.*' => ['required', 'date_format:Y-m'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $months = $this->input('months');

        if (! is_array($months)) {
            return;
        }

        $cleaned = collect($months)
            ->filter(fn($month) => is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->merge([
            'months' => $cleaned,
        ]);
    }
}
