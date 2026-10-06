<?php

namespace App\Services\StudyTracker\DataDeletion;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Builds the DeletionPlan for a user and a set of categories: root rows,
 * then a fixpoint over the registry's foreign-key edges, then a check that
 * every collected row belongs to the user.
 */
class DataCollector
{
    private const CHUNK = 500;

    public function __construct(private DataCategoryRegistry $registry) {}

    /** @param  list<string>  $categories */
    public function collect(User $user, array $categories, bool $lock = false): DeletionPlan
    {
        $categories = array_values(array_unique($categories));
        $rows = [];

        foreach ($categories as $category) {
            foreach ($this->registry->roots($category, $user->id) as $table => $query) {
                if ($lock) {
                    $query->lockForUpdate();
                }

                foreach ($query->get() as $row) {
                    $rows[$table][(int) $row->id] = (array) $row;
                }
            }
        }

        // Required edges pull in child rows until nothing changes.
        do {
            $changed = false;

            foreach ($this->registry->edges(required: true) as [$child, $column, $parent]) {
                foreach ($this->childrenOf($child, $column, array_keys($rows[$parent] ?? []), $lock) as $row) {
                    if (! isset($rows[$child][(int) $row->id])) {
                        $rows[$child][(int) $row->id] = (array) $row;
                        $changed = true;
                    }
                }
            }
        } while ($changed);

        // Nullable edges: rows that stay keep living, minus the reference.
        $references = [];
        foreach ($this->registry->edges(required: false) as [$child, $column, $parent]) {
            foreach ($this->childrenOf($child, $column, array_keys($rows[$parent] ?? []), $lock) as $row) {
                if (! isset($rows[$child][(int) $row->id])) {
                    $references[] = [
                        'table' => $child,
                        'id' => (int) $row->id,
                        'column' => $column,
                        'value' => (int) $row->{$column},
                    ];
                }
            }
        }

        foreach ($rows as $table => $tableRows) {
            foreach ($tableRows as $id => $row) {
                if ((int) ($row['user_id'] ?? 0) !== $user->id) {
                    throw new RuntimeException(
                        "Data deletion plan for user {$user->id} reached {$table}#{$id} owned by another user."
                    );
                }
            }
        }

        $ordered = [];
        foreach ($this->registry->registeredTables() as $table) {
            if (! empty($rows[$table])) {
                ksort($rows[$table]);
                $ordered[$table] = $rows[$table];
            }
        }

        $clearsPreferences = collect($categories)->contains(fn ($c) => $this->registry->clearsPreferences($c));

        return new DeletionPlan(
            userId: $user->id,
            categories: $categories,
            tables: $ordered,
            references: $references,
            clearsPreferences: $clearsPreferences,
            preferences: $clearsPreferences ? $this->rawPreferences($user) : null,
            registry: $this->registry,
        );
    }

    /** @param  list<int>  $parentIds */
    private function childrenOf(string $table, string $column, array $parentIds, bool $lock): array
    {
        $found = [];

        foreach (array_chunk($parentIds, self::CHUNK) as $chunk) {
            $query = DB::table($table)->whereIn($column, $chunk);
            if ($lock) {
                $query->lockForUpdate();
            }
            array_push($found, ...$query->get()->all());
        }

        return $found;
    }

    private function rawPreferences(User $user): ?string
    {
        $value = DB::table('users')->where('id', $user->id)->value('study_preferences');

        return $value === null ? null : (string) $value;
    }
}
