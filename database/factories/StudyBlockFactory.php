<?php

namespace Database\Factories;

use App\Models\StudyBlock;
use App\Models\StudyWeek;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyBlock>
 */
class StudyBlockFactory extends Factory
{
    protected $model = StudyBlock::class;

    public function definition(): array
    {
        return [
            'study_week_id' => StudyWeek::factory(),
            'user_id' => fn (array $attrs) => StudyWeek::find($attrs['study_week_id'])->user_id,
            'block_date' => fn (array $attrs) => StudyWeek::find($attrs['study_week_id'])->week_start->toDateString(),
            'slot' => 'morning',
            'lane' => 'major',
            'planned_minutes' => 90,
            'status' => 'planned',
        ];
    }
}
