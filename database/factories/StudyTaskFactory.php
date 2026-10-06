<?php

namespace Database\Factories;

use App\Models\StudyTask;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyTask>
 */
class StudyTaskFactory extends Factory
{
    protected $model = StudyTask::class;

    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'user_id' => fn (array $attrs) => Topic::find($attrs['topic_id'])->user_id,
            'task_type' => 'revision',
            'revision_no' => 1,
            'title' => 'Revision 1',
            'scheduled_date' => today()->toDateString(),
            'status' => 'pending',
        ];
    }

    public function learn(): static
    {
        return $this->state(fn () => ['task_type' => 'learn', 'revision_no' => null, 'title' => 'Learn']);
    }

    public function revision(int $no = 1): static
    {
        return $this->state(fn () => ['task_type' => 'revision', 'revision_no' => $no, 'title' => "Revision {$no}"]);
    }

    public function completed(?string $at = null): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => $at ?? now(),
            'locked_at' => $at ?? now(),
            'is_date_locked' => true,
        ]);
    }

    public function missed(): static
    {
        return $this->state(fn () => ['status' => 'missed']);
    }
}
