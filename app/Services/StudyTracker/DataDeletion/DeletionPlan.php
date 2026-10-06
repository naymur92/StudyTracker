<?php

namespace App\Services\StudyTracker\DataDeletion;

/**
 * The exact rows a set of categories covers for one user, as raw stored
 * values, plus the references that must be cleared on rows that stay.
 */
class DeletionPlan
{
    /**
     * @param  list<string>  $categories
     * @param  array<string, array<int, array<string, mixed>>>  $tables  table => id => raw row
     * @param  list<array{table: string, id: int, column: string, value: int}>  $references
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $categories,
        private readonly array $tables,
        private readonly array $references,
        private readonly bool $clearsPreferences,
        private readonly ?string $preferences,
        private readonly DataCategoryRegistry $registry,
    ) {}

    /** @return array<string, array<int, array<string, mixed>>> */
    public function tables(): array
    {
        return $this->tables;
    }

    /** @return list<int> */
    public function ids(string $table): array
    {
        return array_keys($this->tables[$table] ?? []);
    }

    /** @return list<array{table: string, id: int, column: string, value: int}> */
    public function references(): array
    {
        return $this->references;
    }

    public function clearsPreferences(): bool
    {
        return $this->clearsPreferences;
    }

    /** Raw stored study_preferences JSON (null when the user has none). */
    public function preferences(): ?string
    {
        return $this->preferences;
    }

    /** Rows per table, for archive verification. @return array<string, int> */
    public function tableCounts(): array
    {
        return array_map('count', $this->tables);
    }

    /**
     * Rows per record kind (topics split into topics and mistakes), plus
     * study_preferences when they will be reset. @return array<string, int>
     */
    public function counts(): array
    {
        $counts = [];

        foreach ($this->tables as $table => $rows) {
            foreach ($rows as $row) {
                $kind = $this->registry->recordKind($table, $row);
                $counts[$kind] = ($counts[$kind] ?? 0) + 1;
            }
        }

        if ($this->clearsPreferences && $this->preferences !== null) {
            $counts['study_preferences'] = 1;
        }

        // Stable display order.
        return array_intersect_key(
            array_replace(DataCategoryRegistry::RECORD_LABELS, $counts),
            $counts
        );
    }

    public function total(): int
    {
        return array_sum($this->counts());
    }

    public function isEmpty(): bool
    {
        return $this->total() === 0 && $this->references === [];
    }
}
