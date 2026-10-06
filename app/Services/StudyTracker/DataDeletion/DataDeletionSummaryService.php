<?php

namespace App\Services\StudyTracker\DataDeletion;

use App\Models\User;

/**
 * Describes what deletion categories cover right now, for the user's
 * request form, the request-time snapshot and the admin live summary.
 * Uses the same collector as the deletion itself.
 */
class DataDeletionSummaryService
{
    public const SAMPLE_LIMIT = 20;

    /** Record kinds that get sample titles in the admin summary. */
    private const SAMPLED = ['topics', 'mistakes', 'categories'];

    public function __construct(
        private DataCollector $collector,
        private DataCategoryRegistry $registry,
    ) {}

    /**
     * Every category on its own, with its current counts.
     *
     * @return list<array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        return array_map(function (string $key) use ($user) {
            $definition = $this->registry->definition($key);
            $counts = $this->collector->collect($user, [$key])->counts();

            return [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'notes' => $definition['notes'],
                'total' => array_sum($counts),
                'records' => $this->records($counts),
            ];
        }, $this->registry->keys());
    }

    /**
     * Snapshot stored on a request when it is created.
     *
     * @param  list<string>  $categories
     * @return array{categories: array<string, array<string, int>>, records: array<string, int>}
     */
    public function snapshot(User $user, array $categories): array
    {
        $perCategory = [];
        foreach ($categories as $key) {
            $perCategory[$key] = $this->collector->collect($user, [$key])->counts();
        }

        return [
            'categories' => $perCategory,
            'records' => $this->collector->collect($user, $categories)->counts(),
        ];
    }

    /**
     * Detailed live summary of a set of categories collected together.
     *
     * @param  list<string>  $categories
     * @return array<string, mixed>
     */
    public function forCategories(User $user, array $categories): array
    {
        $plan = $this->collector->collect($user, $categories);
        $counts = $plan->counts();

        $dates = [];
        $samples = [];
        foreach ($plan->tables() as $table => $rows) {
            $dateColumn = $this->registry->dateColumn($table);
            $titleColumn = $this->registry->titleColumn($table);

            foreach ($rows as $row) {
                $kind = $this->registry->recordKind($table, $row);
                $date = $row[$dateColumn] ?? $row['created_at'] ?? null;

                if ($date !== null) {
                    $day = substr((string) $date, 0, 10);
                    $dates[$kind]['earliest'] = min($dates[$kind]['earliest'] ?? $day, $day);
                    $dates[$kind]['latest'] = max($dates[$kind]['latest'] ?? $day, $day);
                }

                if ($titleColumn && in_array($kind, self::SAMPLED, true)
                    && count($samples[$kind] ?? []) < self::SAMPLE_LIMIT) {
                    $samples[$kind][] = (string) $row[$titleColumn];
                }
            }
        }

        $records = array_map(fn (array $record) => $record + [
            'earliest' => $dates[$record['key']]['earliest'] ?? null,
            'latest' => $dates[$record['key']]['latest'] ?? null,
        ], $this->records($counts));

        $references = [];
        foreach ($plan->references() as $reference) {
            $key = $reference['table'].'.'.$reference['column'];
            $references[$key] ??= [
                'table' => $reference['table'],
                'column' => $reference['column'],
                'label' => $this->registry->recordLabel($reference['table']),
                'count' => 0,
            ];
            $references[$key]['count']++;
        }

        return [
            'categories' => $categories,
            'total' => array_sum($counts),
            'counts' => $counts,
            'records' => $records,
            'samples' => collect(self::SAMPLED)
                ->filter(fn ($kind) => isset($counts[$kind]))
                ->mapWithKeys(fn ($kind) => [$kind => [
                    'label' => $this->registry->recordLabel($kind),
                    'total' => $counts[$kind],
                    'titles' => $samples[$kind] ?? [],
                ]])
                ->all(),
            'references' => array_values($references),
            'resets_preferences' => $plan->clearsPreferences(),
        ];
    }

    /**
     * Per record kind: count at request time, count now and the change.
     *
     * @param  array<string, int>  $snapshot
     * @param  array<string, int>  $live
     * @return array<string, array{label: string, before: int, now: int, change: int}>
     */
    public function diff(array $snapshot, array $live): array
    {
        $keys = array_values(array_unique(array_merge(array_keys($snapshot), array_keys($live))));
        $order = array_keys(DataCategoryRegistry::RECORD_LABELS);
        usort($keys, fn ($a, $b) => array_search($a, $order, true) <=> array_search($b, $order, true));

        $diff = [];
        foreach ($keys as $key) {
            $before = (int) ($snapshot[$key] ?? 0);
            $now = (int) ($live[$key] ?? 0);
            $diff[$key] = [
                'label' => $this->registry->recordLabel($key),
                'before' => $before,
                'now' => $now,
                'change' => $now - $before,
            ];
        }

        return $diff;
    }

    /** @param  array<string, int>  $counts */
    private function records(array $counts): array
    {
        $records = [];
        foreach (DataCategoryRegistry::RECORD_LABELS as $key => $label) {
            if (isset($counts[$key])) {
                $records[] = ['key' => $key, 'label' => $label, 'count' => $counts[$key]];
            }
        }

        return $records;
    }
}
