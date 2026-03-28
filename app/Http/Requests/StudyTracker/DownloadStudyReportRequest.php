<?php

namespace App\Http\Requests\StudyTracker;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DownloadStudyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'start_month' => ['required', 'date_format:Y-m'],
            'end_month' => ['required', 'date_format:Y-m'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('start_month') || ! $this->filled('end_month')) {
                return;
            }

            try {
                $start = Carbon::createFromFormat('Y-m', (string) $this->input('start_month'))->startOfMonth();
                $end = Carbon::createFromFormat('Y-m', (string) $this->input('end_month'))->startOfMonth();
            } catch (\Throwable $exception) {
                return;
            }

            if ($start->greaterThan($end)) {
                $validator->errors()->add('end_month', 'End month must be the same as or after start month.');
                return;
            }

            // Same month = 1 month range, next month = 2 months range.
            $monthsCount = $start->diffInMonths($end) + 1;
            if ($monthsCount > 2) {
                $validator->errors()->add('end_month', 'You can download reports for a maximum range of 2 months.');
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function months(): array
    {
        $start = Carbon::createFromFormat('Y-m', (string) $this->input('start_month'))->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', (string) $this->input('end_month'))->startOfMonth();

        return collect(CarbonPeriod::create($start, '1 month', $end))
            ->map(fn(Carbon $date) => $date->format('Y-m'))
            ->values()
            ->all();
    }
}
