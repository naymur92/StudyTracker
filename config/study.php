<?php

/*
|--------------------------------------------------------------------------
| Study Tracker learning-system settings
|--------------------------------------------------------------------------
|
| Built-in schedule presets, per-user preference defaults, weekly gear
| templates and review constants used by the adaptive learning system.
|
*/

return [

    // Built-in review schedule presets (offsets are days after first study).
    'schedule_presets' => [
        'standard' => [
            'name' => 'Standard (career-long)',
            'description' => 'Classic +1, +7, +30, +90 days. Good for material you need for years, such as backend skills.',
            'offsets' => [1, 7, 30, 90],
            'repeat_every_days' => null,
        ],
        'exam_short' => [
            'name' => 'Exam soon (short horizon)',
            'description' => 'Short gaps for a test a few weeks away, then weekly reviews until the exam date you set.',
            'offsets' => [1, 3, 7, 14],
            'repeat_every_days' => 7,
        ],
        'long_horizon' => [
            'name' => 'Long horizon',
            'description' => 'Growing gaps for material you need for months or years, then a review every 90 days.',
            'offsets' => [1, 3, 7, 21, 60],
            'repeat_every_days' => 90,
        ],
        'mistakes' => [
            'name' => 'Mistakes',
            'description' => 'Fix errors while fresh: +1, +3, +7 days, then merge into the parent topic.',
            'offsets' => [1, 3, 7],
            'repeat_every_days' => null,
        ],
    ],

    // Built-in fallback when no template exists at all.
    'builtin_offsets' => [1, 7, 30, 90],

    // Per-user study preference defaults.
    'preference_defaults' => [
        'review_budget_minutes' => 25,
        'review_debt_threshold_minutes' => 30,
        'minutes_per_review' => 3,
        'weekly_new_topic_cap' => 8,
        'week_starts_on' => 0, // 0 = Sunday
        'off_days' => [5, 6], // Friday, Saturday
        'success_threshold_percent' => 80,
    ],

    // Weekly gear templates: blocks per office day / off day.
    // Yellow's off-day block A is only created on the first off day of the week.
    'gear_templates' => [
        'green' => [
            'office' => [
                ['slot' => 'morning', 'lane' => 'major', 'minutes' => 90],
                ['slot' => 'review', 'lane' => 'review', 'minutes' => 20],
                ['slot' => 'minor', 'lane' => 'minor', 'minutes' => 20],
            ],
            'off' => [
                ['slot' => 'block_a', 'lane' => 'major', 'minutes' => 150],
                ['slot' => 'block_b', 'lane' => 'minor', 'minutes' => 75],
                ['slot' => 'review', 'lane' => 'review', 'minutes' => 15],
            ],
            'first_off_day_only' => [],
        ],
        'yellow' => [
            'office' => [
                ['slot' => 'morning', 'lane' => 'major', 'minutes' => 90],
                ['slot' => 'review', 'lane' => 'review', 'minutes' => 15],
            ],
            'off' => [
                ['slot' => 'review', 'lane' => 'review', 'minutes' => 15],
            ],
            'first_off_day_only' => [
                ['slot' => 'block_a', 'lane' => 'major', 'minutes' => 150],
            ],
        ],
        'red' => [
            'office' => [
                ['slot' => 'review', 'lane' => 'review', 'minutes' => 20],
            ],
            'off' => [
                ['slot' => 'review', 'lane' => 'review', 'minutes' => 20],
            ],
            'first_off_day_only' => [],
        ],
    ],

    // Display / sort order of block slots within a day.
    'slot_order' => ['morning', 'block_a', 'block_b', 'review', 'minor', 'other'],

    // Review constants.
    'review' => [
        'max_recall_questions' => 10,
        'timing_sample_size' => 30,
        'timing_min_samples' => 5,
        'per_review_min_clamp' => 1,
        'per_review_max_clamp' => 15,
        'max_review_seconds' => 3600,
        'debt_lookback_days' => 15,
    ],
];
