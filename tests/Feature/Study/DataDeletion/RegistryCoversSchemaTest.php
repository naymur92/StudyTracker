<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Services\StudyTracker\DataDeletion\DataCategoryRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Fails when a new table holding user data is neither covered by a deletion
 * category nor deliberately excluded.
 */
class RegistryCoversSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_table_with_a_user_id_is_registered_or_excluded(): void
    {
        $known = array_merge(
            (new DataCategoryRegistry)->registeredTables(),
            DataCategoryRegistry::EXCLUDED_TABLES,
        );

        $uncovered = collect(Schema::getTableListing(schemaQualified: false))
            ->filter(fn (string $table) => in_array('user_id', Schema::getColumnListing($table), true))
            ->reject(fn (string $table) => in_array($table, $known, true))
            ->values()
            ->all();

        $this->assertSame([], $uncovered, 'Register these tables in DataCategoryRegistry or add them to EXCLUDED_TABLES: '.implode(', ', $uncovered));
    }

    public function test_every_registered_table_exists(): void
    {
        foreach ((new DataCategoryRegistry)->registeredTables() as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} is registered but does not exist");
            $this->assertTrue(Schema::hasColumn($table, 'user_id'), "{$table} has no user_id column");
        }
    }
}
