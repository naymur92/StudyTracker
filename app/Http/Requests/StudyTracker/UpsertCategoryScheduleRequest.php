<?php

namespace App\Http\Requests\StudyTracker;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertCategoryScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'preset_key' => ['nullable', 'string', Rule::in([...array_keys(config('study.schedule_presets')), 'custom'])],
            'offsets' => ['nullable', 'array', 'min:1', 'max:10'],
            'offsets.*' => ['integer', 'min:1', 'max:3650'],
            'repeat_every_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'repeat_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'apply_to_existing_topics' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $schedule = $this->schedule();

                if ($schedule['offsets'] === []) {
                    $validator->errors()->add('offsets', 'Choose a preset or enter at least one offset.');

                    return;
                }

                $offsets = $schedule['offsets'];
                for ($i = 1; $i < count($offsets); $i++) {
                    if ($offsets[$i] <= $offsets[$i - 1]) {
                        $validator->errors()->add('offsets', 'Offsets must be strictly increasing (e.g. 1, 3, 7, 14).');

                        return;
                    }
                }

                if ($schedule['repeat_until'] !== null && $schedule['repeat_every_days'] === null) {
                    $validator->errors()->add('repeat_until', 'A repeat-until date needs a repeat interval.');
                }
            },
        ];
    }

    /**
     * The effective schedule: a preset fills in whatever the request leaves out.
     *
     * @return array{preset_key: string, offsets: int[], repeat_every_days: ?int, repeat_until: ?string}
     */
    public function schedule(): array
    {
        $key = $this->input('preset_key') ?: 'custom';
        $preset = $key !== 'custom' ? config("study.schedule_presets.{$key}") : null;

        $offsets = $this->filled('offsets')
            ? array_map('intval', (array) $this->input('offsets'))
            : ($preset['offsets'] ?? []);

        $repeat = $this->exists('repeat_every_days')
            ? ($this->input('repeat_every_days') !== null ? (int) $this->input('repeat_every_days') : null)
            : ($preset['repeat_every_days'] ?? null);

        return [
            'preset_key' => $key,
            'offsets' => array_values($offsets),
            'repeat_every_days' => $repeat,
            'repeat_until' => $this->input('repeat_until') ?: null,
        ];
    }
}
