<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'status' => 'active',
            'kind' => Topic::KIND_TOPIC,
            'first_study_date' => today()->toDateString(),
        ];
    }

    public function withSchedule(array $offsets, ?int $repeatEvery = null, ?string $repeatUntil = null): static
    {
        return $this->state(fn () => [
            'srs_offsets' => $offsets,
            'srs_repeat_every_days' => $repeatEvery,
            'srs_repeat_until' => $repeatUntil,
            'srs_schedule_source' => 'builtin',
        ]);
    }

    public function mistake(): static
    {
        return $this->state(fn () => [
            'kind' => Topic::KIND_MISTAKE,
            'srs_offsets' => [1, 3, 7],
            'srs_schedule_source' => 'preset:mistakes',
            'mistake_details' => [
                'my_answer' => 'False',
                'correct_answer' => 'Not Given',
                'cause' => 'concept',
                'source' => null,
            ],
        ]);
    }
}
