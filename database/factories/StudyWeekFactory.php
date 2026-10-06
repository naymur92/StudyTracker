<?php

namespace Database\Factories;

use App\Models\StudyWeek;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyWeek>
 */
class StudyWeekFactory extends Factory
{
    protected $model = StudyWeek::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'week_start' => today()->startOfWeek(Carbon::SUNDAY)->toDateString(),
            'gear' => 'green',
        ];
    }
}
