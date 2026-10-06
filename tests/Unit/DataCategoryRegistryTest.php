<?php

namespace Tests\Unit;

use App\Services\StudyTracker\DataDeletion\DataCategoryRegistry;
use PHPUnit\Framework\TestCase;

class DataCategoryRegistryTest extends TestCase
{
    public function test_keys_are_exactly_the_eight_categories(): void
    {
        $this->assertSame([
            'topics',
            'mistakes',
            'practice_logs',
            'weekly_plans',
            'categories',
            'study_settings',
            'report_history',
            'review_history',
        ], (new DataCategoryRegistry)->keys());
    }

    public function test_every_edge_table_is_in_the_delete_order(): void
    {
        $registry = new DataCategoryRegistry;
        $order = DataCategoryRegistry::DELETE_ORDER;

        foreach ($registry->edges() as [$child, $column, $parent]) {
            $this->assertContains($child, $order, "{$child}.{$column} child missing from delete order");
            $this->assertContains($parent, $order, "{$parent} parent missing from delete order");
        }
    }

    public function test_required_children_are_deleted_before_their_parents(): void
    {
        $order = array_flip(DataCategoryRegistry::DELETE_ORDER);

        foreach ((new DataCategoryRegistry)->edges(required: true) as [$child, $column, $parent]) {
            $this->assertLessThan($order[$parent], $order[$child], "{$child}.{$column} must be deleted before {$parent}");
        }
    }

    public function test_every_category_has_label_description_and_notes(): void
    {
        $registry = new DataCategoryRegistry;

        foreach ($registry->keys() as $key) {
            $definition = $registry->definition($key);
            $this->assertNotEmpty($definition['label']);
            $this->assertNotEmpty($definition['description']);
            $this->assertNotEmpty($definition['notes']);
        }
    }
}
