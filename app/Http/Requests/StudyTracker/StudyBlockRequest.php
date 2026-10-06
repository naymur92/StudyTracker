<?php

namespace App\Http\Requests\StudyTracker;

use App\Models\StudyBlock;
use App\Models\StudyWeek;
use App\Services\IdHasher;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Create (POST, needs the week) and update (PATCH) a study block. */
class StudyBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'block_date' => [$required, 'date_format:Y-m-d'],
            'slot' => [$required, Rule::in(StudyBlock::SLOTS)],
            'lane' => [$required, Rule::in(StudyBlock::LANES)],
            'planned_task' => ['nullable', 'string', 'max:300'],
            'planned_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'status' => ['sometimes', Rule::in(StudyBlock::STATUSES)],
            'note' => ['nullable', 'string', 'max:500'],
            'category_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q
                    ->where(fn ($w) => $w->where('user_id', auth()->id())->orWhereNull('user_id'))
                    ->whereNull('deleted_at')),
            ],
            'topic_id' => [
                'nullable', 'integer',
                Rule::exists('topics', 'id')->where(fn ($q) => $q->where('user_id', auth()->id())->whereNull('deleted_at')),
            ],
        ];
    }

    public function after(): array
    {
        return [
            // done / partial / missed record what happened, so a future block
            // cannot carry them (it would count in the week's score early).
            // Planning edits and red stay open for future days.
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['block_date', 'status'])) {
                    return;
                }

                $block = $this->route('block');
                $status = $this->input('status', $block?->status ?? 'planned');
                $date = $this->input('block_date', $block?->block_date?->toDateString());

                if (in_array($status, StudyBlock::OUTCOME_STATUSES, true) && $date && Carbon::parse($date)->gt(today())) {
                    $validator->errors()->add(
                        $this->has('status') ? 'status' : 'block_date',
                        'A block can be marked done, partial or missed only on or after its day.'
                    );
                }
            },
            function (Validator $validator) {
                if ($validator->errors()->has('block_date') || ! $this->filled('block_date')) {
                    return;
                }

                $week = $this->route('week') ?? $this->route('block')?->week;
                if (! $week instanceof StudyWeek) {
                    return;
                }

                $date = Carbon::parse($this->input('block_date'));
                if ($date->lt($week->week_start) || $date->gt($week->weekEnd())) {
                    $validator->errors()->add('block_date', 'The block must be within the plan\'s week ('.$week->week_start->toDateString().' to '.$week->weekEnd()->toDateString().').');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['category_id', 'topic_id'] as $key) {
            if ($this->filled($key)) {
                $this->merge([$key => IdHasher::decode((string) $this->input($key)) ?? -1]);
            }
        }
    }
}
