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
        'study_profile' => 'job_holder', // job_holder | student
    ],

    // Weekly gear templates per study profile. Each day is a workday (office
    // day for a job holder, class day for a student) or an off day (from the
    // user's `off_days`). `first_off_day_only` blocks go on the week's first
    // off day only.
    'gear_templates' => [
        'job_holder' => [
            'green' => [
                'workday' => [
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
                'workday' => [
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
                'workday' => [
                    ['slot' => 'review', 'lane' => 'review', 'minutes' => 20],
                ],
                'off' => [
                    ['slot' => 'review', 'lane' => 'review', 'minutes' => 20],
                ],
                'first_off_day_only' => [],
            ],
        ],
        'student' => [
            'green' => [
                'workday' => [
                    ['slot' => 'class_recap', 'lane' => 'major', 'minutes' => 30],
                    ['slot' => 'deep', 'lane' => 'major', 'minutes' => 90],
                    ['slot' => 'review', 'lane' => 'review', 'minutes' => 25],
                    ['slot' => 'minor', 'lane' => 'minor', 'minutes' => 30],
                ],
                'off' => [
                    ['slot' => 'block_a', 'lane' => 'major', 'minutes' => 150],
                    ['slot' => 'block_b', 'lane' => 'minor', 'minutes' => 120],
                    ['slot' => 'review', 'lane' => 'review', 'minutes' => 25],
                ],
                'first_off_day_only' => [],
            ],
            'yellow' => [
                'workday' => [
                    ['slot' => 'class_recap', 'lane' => 'major', 'minutes' => 20],
                    ['slot' => 'deep', 'lane' => 'major', 'minutes' => 60],
                    ['slot' => 'review', 'lane' => 'review', 'minutes' => 20],
                ],
                'off' => [
                    ['slot' => 'block_a', 'lane' => 'major', 'minutes' => 120],
                    ['slot' => 'review', 'lane' => 'review', 'minutes' => 20],
                ],
                'first_off_day_only' => [],
            ],
            'red' => [
                'workday' => [
                    ['slot' => 'review', 'lane' => 'review', 'minutes' => 20],
                ],
                'off' => [
                    ['slot' => 'review', 'lane' => 'review', 'minutes' => 20],
                ],
                'first_off_day_only' => [],
            ],
        ],
    ],

    // Gear labels and descriptions per study profile (shown with gear options).
    'gear_descriptions' => [
        'job_holder' => [
            'green' => ['label' => 'Green', 'description' => 'Normal week: a morning deep block on office days, long blocks on off days'],
            'yellow' => ['label' => 'Yellow', 'description' => 'Busy week (release, guests, Ramadan): mornings, short reviews, one Block A'],
            'red' => ['label' => 'Red', 'description' => 'Eid, illness, travel: 20 minutes of reviews a day'],
        ],
        'student' => [
            'green' => ['label' => 'Green', 'description' => 'Normal week: class recap and a deep block on class days, two long blocks on free days'],
            'yellow' => ['label' => 'Yellow', 'description' => 'Assignment or deadline week: shorter recap and deep block, Block A on free days'],
            'red' => ['label' => 'Red', 'description' => 'Illness, travel, Eid: 20 minutes of reviews a day'],
        ],
    ],

    'study_profiles' => ['job_holder', 'student'],

    // Display / sort order of block slots within a day.
    'slot_order' => ['morning', 'class_recap', 'deep', 'block_a', 'block_b', 'review', 'minor', 'other'],

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
