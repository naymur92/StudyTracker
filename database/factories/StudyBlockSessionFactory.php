<?php

namespace Database\Factories;

use App\Models\StudyBlock;
use App\Models\StudyBlockSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyBlockSession>
 */
class StudyBlockSessionFactory extends Factory
{
    protected $model = StudyBlockSession::class;

    /** An ended 30-minute run. */
    public function definition(): array
    {
        return [
            'study_block_id' => StudyBlock::factory(),
            'user_id' => fn (array $attrs) => StudyBlock::find($attrs['study_block_id'])->user_id,
            'started_at' => now()->subMinutes(30),
            'used_seconds' => 1800,
            'ended_at' => now(),
            'end_reason' => StudyBlockSession::END_STOPPED,
        ];
    }

    /** A run still running since $startedAt. */
    public function running($startedAt = null): static
    {
        return $this->state(fn () => [
            'started_at' => $startedAt ?? now(),
            'resumed_at' => $startedAt ?? now(),
            'used_seconds' => 0,
            'ended_at' => null,
            'end_reason' => null,
        ])->afterMaking(fn (StudyBlockSession $s) => $s->active_user_id = $s->user_id);
    }
}
