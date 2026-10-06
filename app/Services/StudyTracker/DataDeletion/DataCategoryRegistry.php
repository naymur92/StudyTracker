<?php

namespace App\Services\StudyTracker\DataDeletion;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Declares what each deletable data category covers. The summary, archive,
 * deletion and restore all read from here, so they cannot drift apart.
 *
 * A category names root rows (always scoped to one user, soft-deleted rows
 * included). DataCollector then follows the foreign-key edges: a required
 * edge pulls the child rows in (they cannot exist without the parent), a
 * nullable edge records a reference to clear and keeps the child.
 */
class DataCategoryRegistry
{
    public const CATEGORIES = [
        'topics' => [
            'label' => 'Topics',
            'description' => 'Your topics with their study tasks, revisions and practice logs.',
            'notes' => [
                'Study tasks and practice logs of these topics are deleted too.',
                'Mistakes linked to these topics are kept, without a parent topic.',
                'Weekly plan blocks keep their slot but lose the topic link.',
            ],
        ],
        'mistakes' => [
            'label' => 'Mistakes',
            'description' => 'Your mistake notebook entries and their review tasks.',
            'notes' => [
                'Review tasks and practice logs of these mistakes are deleted too.',
            ],
        ],
        'practice_logs' => [
            'label' => 'Practice logs',
            'description' => 'Every practice session you logged.',
            'notes' => [
                'Practice minutes disappear from reports and the dashboard.',
            ],
        ],
        'weekly_plans' => [
            'label' => 'Weekly plans',
            'description' => 'Your weekly plans, their blocks and reflections.',
            'notes' => [
                'All plans, blocks and weekly scores are removed.',
                'Timer runs of these blocks are deleted too.',
            ],
        ],
        'categories' => [
            'label' => 'Categories',
            'description' => 'Categories you created, with their review schedules.',
            'notes' => [
                'Topics in these categories are kept but become uncategorised.',
                'Weekly plan blocks lose the category link.',
                'System categories are not affected.',
            ],
        ],
        'study_settings' => [
            'label' => 'Study settings',
            'description' => 'Revision templates, per-category review schedules and study preferences.',
            'notes' => [
                'Your study preferences return to the defaults.',
                'Existing topics keep the schedule they already have.',
            ],
        ],
        'report_history' => [
            'label' => 'Report history',
            'description' => 'The record of study reports emailed to you.',
            'notes' => [
                'Emails already sent are not recalled.',
            ],
        ],
        'review_history' => [
            'label' => 'Review-load history',
            'description' => 'Daily review-load snapshots used to detect review debt.',
            'notes' => [
                'Review-debt detection starts again from scratch.',
            ],
        ],
    ];

    /** [child table, child column, parent table, required?] */
    public const EDGES = [
        ['study_tasks', 'topic_id', 'topics', true],
        ['practice_logs', 'topic_id', 'topics', true],
        ['practice_logs', 'task_id', 'study_tasks', false],
        ['study_tasks', 'parent_task_id', 'study_tasks', false],
        ['topics', 'parent_topic_id', 'topics', false],
        ['topics', 'category_id', 'categories', false],
        ['study_blocks', 'study_week_id', 'study_weeks', true],
        ['study_block_sessions', 'study_block_id', 'study_blocks', true],
        ['study_blocks', 'topic_id', 'topics', false],
        ['study_blocks', 'category_id', 'categories', false],
        ['category_review_schedules', 'category_id', 'categories', true],
    ];

    /** Children first; restore walks it in reverse. */
    public const DELETE_ORDER = [
        'practice_logs',
        'study_tasks',
        'study_block_sessions',
        'study_blocks',
        'study_weeks',
        'topics',
        'category_review_schedules',
        'categories',
        'topic_revision_templates',
        'emailed_study_reports',
        'review_load_snapshots',
    ];

    /** Unique keys (besides id) that a restored row may clash with. */
    public const UNIQUE_KEYS = [
        'topics' => ['user_id', 'slug'],
        'study_weeks' => ['user_id', 'week_start'],
        'review_load_snapshots' => ['user_id', 'snapshot_date'],
        'category_review_schedules' => ['user_id', 'category_id'],
    ];

    /** Natural date column per table, for summary date ranges. */
    public const DATE_COLUMNS = [
        'topics' => 'first_study_date',
        'study_tasks' => 'scheduled_date',
        'practice_logs' => 'practiced_on',
        'study_weeks' => 'week_start',
        'study_blocks' => 'block_date',
        'study_block_sessions' => 'started_at',
        'review_load_snapshots' => 'snapshot_date',
    ];

    /** Column shown as a sample title in the admin summary. */
    public const TITLE_COLUMNS = [
        'topics' => 'title',
        'categories' => 'name',
    ];

    /** Labels for the record kinds a plan counts (topics split by kind). */
    public const RECORD_LABELS = [
        'topics' => 'Topics',
        'mistakes' => 'Mistakes',
        'study_tasks' => 'Study tasks',
        'practice_logs' => 'Practice logs',
        'study_weeks' => 'Weekly plans',
        'study_blocks' => 'Weekly plan blocks',
        'study_block_sessions' => 'Block timer runs',
        'categories' => 'Categories',
        'category_review_schedules' => 'Category review schedules',
        'topic_revision_templates' => 'Revision templates',
        'emailed_study_reports' => 'Emailed reports',
        'review_load_snapshots' => 'Review-load snapshots',
        'study_preferences' => 'Study preferences',
    ];

    /**
     * Tables with a user_id column that requests never touch: auth state and
     * audit records. The schema coverage test fails on any table that is
     * neither here nor registered.
     */
    public const EXCLUDED_TABLES = [
        'activity_logs',
        'login_history',
        'oauth_access_tokens',
        'oauth_auth_codes',
        'oauth_device_codes',
        'oauth_refresh_tokens',
        'oauth_clients',
        'sessions',
        'files',
        'forgot_password_codes',
        'email_verification_tokens',
        'data_deletion_requests',
    ];

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys(self::CATEGORIES);
    }

    public function has(string $key): bool
    {
        return isset(self::CATEGORIES[$key]);
    }

    /** @return array{label: string, description: string, notes: list<string>} */
    public function definition(string $key): array
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown data category [{$key}].");
        }

        return self::CATEGORIES[$key];
    }

    public function label(string $key): string
    {
        return $this->definition($key)['label'];
    }

    public function recordLabel(string $record): string
    {
        return self::RECORD_LABELS[$record] ?? $record;
    }

    /** @return list<string> */
    public function registeredTables(): array
    {
        return self::DELETE_ORDER;
    }

    /**
     * Root row queries for one category, keyed by table.
     *
     * @return array<string, Builder>
     */
    public function roots(string $key, int $userId): array
    {
        $owned = fn (string $table) => DB::table($table)->where('user_id', $userId);

        return match ($key) {
            'topics' => ['topics' => $owned('topics')->where('kind', 'topic')],
            'mistakes' => ['topics' => $owned('topics')->where('kind', 'mistake')],
            'practice_logs' => ['practice_logs' => $owned('practice_logs')],
            'weekly_plans' => ['study_weeks' => $owned('study_weeks')],
            'categories' => ['categories' => $owned('categories')],
            'study_settings' => [
                'topic_revision_templates' => $owned('topic_revision_templates'),
                'category_review_schedules' => $owned('category_review_schedules'),
            ],
            'report_history' => ['emailed_study_reports' => $owned('emailed_study_reports')],
            'review_history' => ['review_load_snapshots' => $owned('review_load_snapshots')],
            default => throw new InvalidArgumentException("Unknown data category [{$key}]."),
        };
    }

    /** Whether the category also resets users.study_preferences. */
    public function clearsPreferences(string $key): bool
    {
        return $key === 'study_settings';
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: bool}> */
    public function edges(?bool $required = null): array
    {
        return array_values(array_filter(
            self::EDGES,
            fn (array $edge) => $required === null || $edge[3] === $required
        ));
    }

    public function dateColumn(string $table): string
    {
        return self::DATE_COLUMNS[$table] ?? 'created_at';
    }

    public function titleColumn(string $table): ?string
    {
        return self::TITLE_COLUMNS[$table] ?? null;
    }

    /** Record kind of a raw row: topics rows split into topics and mistakes. */
    public function recordKind(string $table, array $row): string
    {
        if ($table === 'topics') {
            return ($row['kind'] ?? 'topic') === 'mistake' ? 'mistakes' : 'topics';
        }

        return $table;
    }
}
