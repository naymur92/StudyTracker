<?php

namespace Database\Seeders;

use App\Models\TopicRevisionTemplate;
use Illuminate\Database\Seeder;

class TopicRevisionTemplateSeeder extends Seeder
{
    /**
     * Seed the system-default spaced repetition schedule.
     * user_id = null means these are the global fallback templates.
     */
    public function run(): void
    {
        $defaults = [
            ['sequence_no' => 1, 'day_offset' => 1,  'name' => 'Revision 1'],
            ['sequence_no' => 2, 'day_offset' => 7,  'name' => 'Revision 2'],
            ['sequence_no' => 3, 'day_offset' => 30, 'name' => 'Revision 3'],
            ['sequence_no' => 4, 'day_offset' => 90, 'name' => 'Revision 4'],
        ];

        foreach ($defaults as $row) {
            TopicRevisionTemplate::firstOrCreate(
                ['user_id' => null, 'sequence_no' => $row['sequence_no']],
                [
                    'name'       => $row['name'],
                    'day_offset' => $row['day_offset'],
                    'is_active'  => true,
                ]
            );
        }

        $this->command->info('Default revision templates seeded (1, 7, 30, 90 days).');
    }
}
