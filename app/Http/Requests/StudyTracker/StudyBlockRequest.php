<?php

namespace App\Http\Requests\StudyTracker;

use App\Models\StudyBlock;
use App\Models\StudyWeek;
use App\Services\IdHasher;
use App\Services\StudyTracker\BlockTimerService;
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
            // Break pattern: both set or both null (no breaks); see after().
            'break_every_minutes' => ['nullable', 'integer', 'min:10', 'max:120'],
            'break_minutes' => ['nullable', 'integer', 'min:1', 'max:30'],
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

    /** Fields that cannot change while the block's timer is active. */
    public const TIMER_LOCKED_FIELDS = ['block_date', 'planned_minutes', 'status', 'break_every_minutes', 'break_minutes'];

    public function after(): array
    {
        return [
            // A running or paused timer depends on the block's day, minutes,
            // status and breaks; those wait until the timer is stopped.
            function (Validator $validator) {
                $block = $this->route('block');
                if (! $block instanceof StudyBlock || $block->user_id !== $this->user()?->id) {
                    return;
                }

                app(BlockTimerService::class)->settle($this->user());
                if (! $block->activeSession()->exists()) {
                    return;
                }

                foreach (self::TIMER_LOCKED_FIELDS as $field) {
                    if ($this->has($field) && ! $validator->errors()->has($field)) {
                        $validator->errors()->add($field, 'Stop the block\'s timer before changing this.');
                    }
                }
            },
            // A break pattern needs both halves, counting the stored values on update.
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['break_every_minutes', 'break_minutes'])) {
                    return;
                }

                $block = $this->route('block');
                $every = $this->has('break_every_minutes') ? $this->input('break_every_minutes') : $block?->break_every_minutes;
                $break = $this->has('break_minutes') ? $this->input('break_minutes') : $block?->break_minutes;

                if (($every === null) !== ($break === null)) {
                    $missing = $every === null ? 'break_every_minutes' : 'break_minutes';
                    $validator->errors()->add($missing, 'A break pattern needs both the work minutes and the break minutes.');
                }
            },
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
