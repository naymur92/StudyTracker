<?php

namespace App\Services\StudyTracker\DataDeletion;

use App\Models\DataDeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Puts an archive back: same IDs, same stored values, parents first.
 * Current data always wins: clashing rows (and rows that need them) are
 * skipped, taken slugs are renamed, references to rows that are gone are
 * nulled. plan() is the read-only preview; restore() applies it.
 */
class RestoreDataDeletionService
{
    public const REASONS = [
        'id_exists' => 'A row with this ID already exists',
        'unique_exists' => 'Current data already uses the same key',
        'parent_missing' => 'The row it belongs to was skipped or no longer exists',
        'row_missing' => 'The row no longer exists',
        'column_set' => 'The user has set a new value since',
        'target_missing' => 'The linked row no longer exists',
    ];

    /** @var array<string, list<string>> */
    private array $columns = [];

    /** @var array<string, array<int, bool>> */
    private array $existing = [];

    public function __construct(
        private DataArchiveService $archives,
        private DataCategoryRegistry $registry,
    ) {}

    /** Read-only preview of what a restore would do. */
    public function plan(DataDeletionRequest $request): array
    {
        $this->assertRestorable($request);

        return $this->build($request, $this->archives->read($request->archive_path))['report'];
    }

    public function restore(DataDeletionRequest $request, User $admin): array
    {
        $report = DB::transaction(function () use ($request, $admin) {
            $locked = DataDeletionRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $this->assertRestorable($locked);

            ['report' => $report, 'inserts' => $inserts, 'references' => $references, 'preferences' => $preferences]
                = $this->build($locked, $this->archives->read($locked->archive_path));

            foreach ($inserts as $table => $rows) {
                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }

            foreach ($references as $reference) {
                DB::table($reference['table'])
                    ->where('id', $reference['id'])
                    ->whereNull($reference['column'])
                    ->update([$reference['column'] => $reference['value']]);
            }

            if ($preferences !== null) {
                DB::table('users')->where('id', $locked->user_id)->whereNull('study_preferences')
                    ->update(['study_preferences' => $preferences]);
            }

            $locked->forceFill([
                'status' => DataDeletionRequest::STATUS_RESTORED,
                'restored_by' => $admin->id,
                'restored_at' => now(),
                'restore_report' => $report,
            ])->save();

            return $report;
        });

        DataDeletionAudit::log('restored', $request->refresh(), $admin, ['totals' => $report['totals']]);

        return $report;
    }

    public function assertRestorable(DataDeletionRequest $request): void
    {
        if ($request->status !== DataDeletionRequest::STATUS_COMPLETED) {
            throw new RuntimeException('Only a completed request can be restored.');
        }

        if (! $request->hasArchive()) {
            throw new RuntimeException('The archive for this request has been purged.');
        }

        if (! $request->user_id || ! User::whereKey($request->user_id)->exists()) {
            throw new RuntimeException('The user of this request no longer exists.');
        }
    }

    /**
     * @return array{report: array, inserts: array<string, list<array>>, references: list<array>, preferences: ?string}
     */
    private function build(DataDeletionRequest $request, array $document): array
    {
        $this->existing = [];
        $archived = $document['tables'] ?? [];

        $planned = [];   // table => id => true (will be inserted)
        $skippedIds = []; // table => id => true
        $inserts = [];
        $tables = [];
        $skipped = [];
        $usedSlugs = [];

        foreach (array_reverse($this->registry->registeredTables()) as $table) {
            $rows = $this->parentsFirst($table, $archived[$table] ?? []);
            if ($rows === []) {
                continue;
            }

            $columns = $this->columns($table);
            $summary = [
                'label' => $this->registry->recordLabel($table),
                'archived' => count($rows),
                'restored' => 0,
                'skipped' => 0,
                'skip_reasons' => [],
                'renamed' => [],
                'nulled' => 0,
                'dropped_columns' => array_values(array_diff(array_keys($rows[0]), $columns)),
            ];

            $this->primeExisting($table, array_column($rows, 'id'));

            foreach ($rows as $row) {
                $id = (int) $row['id'];
                $reason = $this->skipReason($table, $row, $planned, $skippedIds);

                if ($reason !== null) {
                    $skippedIds[$table][$id] = true;
                    $summary['skipped']++;
                    $summary['skip_reasons'][$reason] = ($summary['skip_reasons'][$reason] ?? 0) + 1;
                    $skipped[] = ['table' => $table, 'id' => $id, 'reason' => $reason, 'title' => $this->titleOf($table, $row)];

                    continue;
                }

                // Nullable links to rows that are neither present nor coming back.
                foreach ($this->registry->edges(required: false) as [$child, $column, $parent]) {
                    if ($child === $table && $row[$column] !== null
                        && ! $this->available($parent, (int) $row[$column], $planned)) {
                        $row[$column] = null;
                        $summary['nulled']++;
                    }
                }

                if ($table === 'topics') {
                    $slug = $this->freeSlug((int) $row['user_id'], (string) $row['slug'], $usedSlugs);
                    if ($slug !== $row['slug']) {
                        $summary['renamed'][] = ['id' => $id, 'from' => $row['slug'], 'to' => $slug];
                        $row['slug'] = $slug;
                    }
                    $usedSlugs[$slug] = true;
                }

                $planned[$table][$id] = true;
                $inserts[$table][] = array_intersect_key($row, array_flip($columns));
                $summary['restored']++;
            }

            $tables[$table] = $summary;
        }

        // Links cleared on rows that stayed.
        $referenceSummary = ['archived' => 0, 'restored' => 0, 'skipped' => 0, 'skip_reasons' => []];
        $references = [];
        foreach ($document['references'] ?? [] as $reference) {
            $referenceSummary['archived']++;
            $current = DB::table($reference['table'])->where('id', $reference['id'])->first([$reference['column']]);
            $parent = $this->parentOf($reference['table'], $reference['column']);

            $reason = match (true) {
                $current === null => 'row_missing',
                $current->{$reference['column']} !== null => 'column_set',
                ! $this->available($parent, (int) $reference['value'], $planned) => 'target_missing',
                default => null,
            };

            if ($reason !== null) {
                $referenceSummary['skipped']++;
                $referenceSummary['skip_reasons'][$reason] = ($referenceSummary['skip_reasons'][$reason] ?? 0) + 1;

                continue;
            }

            $referenceSummary['restored']++;
            $references[] = $reference;
        }

        // Study preferences come back only if the user has none now.
        $preferences = null;
        $preferencesOutcome = null;
        $archivedPreferences = $document['user_columns']['study_preferences'] ?? null;
        if ($archivedPreferences !== null) {
            $current = DB::table('users')->where('id', $request->user_id)->value('study_preferences');
            $preferencesOutcome = $current === null ? 'restored' : 'kept_current';
            $preferences = $current === null ? $archivedPreferences : null;
        }

        $report = [
            'tables' => $tables,
            'skipped' => $skipped,
            'references' => $referenceSummary,
            'preferences' => $preferencesOutcome,
            'totals' => [
                'restored' => array_sum(array_column($tables, 'restored')),
                'skipped' => array_sum(array_column($tables, 'skipped')),
            ],
        ];

        return ['report' => $report, 'inserts' => $inserts, 'references' => $references, 'preferences' => $preferences];
    }

    private function skipReason(string $table, array $row, array $planned, array $skippedIds): ?string
    {
        if ($this->existsInDb($table, (int) $row['id'])) {
            return 'id_exists';
        }

        foreach ($this->registry->edges(required: true) as [$child, $column, $parent]) {
            if ($child !== $table) {
                continue;
            }

            $parentId = (int) $row[$column];
            if (isset($skippedIds[$parent][$parentId]) || ! $this->available($parent, $parentId, $planned)) {
                return 'parent_missing';
            }
        }

        $unique = DataCategoryRegistry::UNIQUE_KEYS[$table] ?? null;
        if ($unique !== null && $table !== 'topics') {
            $query = DB::table($table);
            foreach ($unique as $column) {
                $query->where($column, $row[$column]);
            }
            if ($query->exists()) {
                return 'unique_exists';
            }
        }

        return null;
    }

    /** Self-referencing rows after the rows they point at. */
    private function parentsFirst(string $table, array $rows): array
    {
        $selfColumns = collect($this->registry->edges())
            ->filter(fn ($edge) => $edge[0] === $table && $edge[2] === $table)
            ->pluck(1)
            ->all();

        if ($selfColumns === [] || count($rows) < 2) {
            return $rows;
        }

        $ids = array_flip(array_map(fn ($row) => (int) $row['id'], $rows));
        $placed = [];
        $ordered = [];

        while ($rows !== []) {
            $progress = false;
            foreach ($rows as $index => $row) {
                $ready = collect($selfColumns)->every(fn ($column) => $row[$column] === null
                    || ! isset($ids[(int) $row[$column]])
                    || isset($placed[(int) $row[$column]]));

                if ($ready) {
                    $ordered[] = $row;
                    $placed[(int) $row['id']] = true;
                    unset($rows[$index]);
                    $progress = true;
                }
            }

            if (! $progress) { // a cycle; keep the remaining order
                array_push($ordered, ...array_values($rows));
                break;
            }
        }

        return $ordered;
    }

    private function freeSlug(int $userId, string $slug, array $usedSlugs): string
    {
        $taken = fn (string $candidate) => isset($usedSlugs[$candidate])
            || DB::table('topics')->where('user_id', $userId)->where('slug', $candidate)->exists();

        if (! $taken($slug)) {
            return $slug;
        }

        $base = mb_substr($slug, 0, 200).'-restored';
        $candidate = $base;
        for ($n = 2; $taken($candidate); $n++) {
            $candidate = "{$base}-{$n}";
        }

        return $candidate;
    }

    private function available(string $table, int $id, array $planned): bool
    {
        return isset($planned[$table][$id]) || $this->existsInDb($table, $id);
    }

    private function primeExisting(string $table, array $ids): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            foreach (DB::table($table)->whereIn('id', $chunk)->pluck('id') as $id) {
                $this->existing[$table][(int) $id] = true;
            }
            foreach ($chunk as $id) {
                $this->existing[$table][(int) $id] ??= false;
            }
        }
    }

    private function existsInDb(string $table, int $id): bool
    {
        return $this->existing[$table][$id] ??= DB::table($table)->where('id', $id)->exists();
    }

    private function parentOf(string $table, string $column): string
    {
        foreach ($this->registry->edges() as [$child, $edgeColumn, $parent]) {
            if ($child === $table && $edgeColumn === $column) {
                return $parent;
            }
        }

        throw new RuntimeException("Unknown reference {$table}.{$column} in archive.");
    }

    private function titleOf(string $table, array $row): ?string
    {
        $column = $this->registry->titleColumn($table);

        if ($column) {
            return $row[$column] ?? null;
        }

        $date = $this->registry->dateColumn($table);

        return isset($row[$date]) ? substr((string) $row[$date], 0, 10) : null;
    }

    /** @return list<string> */
    private function columns(string $table): array
    {
        return $this->columns[$table] ??= Schema::getColumnListing($table);
    }
}
