<?php

namespace Tests\Feature\Study\DataDeletion;

use App\Models\Category;
use App\Models\CategoryReviewSchedule;
use App\Models\EmailedStudyReport;
use App\Models\PracticeLog;
use App\Models\ReviewLoadSnapshot;
use App\Models\StudyBlock;
use App\Models\StudyBlockSession;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Models\TopicRevisionTemplate;
use App\Models\User;

/**
 * Small builders for every table a data deletion request can touch.
 */
trait SeedsDeletableData
{
    protected function topicFor(User $user, array $attributes = []): Topic
    {
        return Topic::factory()->create(['user_id' => $user->id] + $attributes);
    }

    protected function mistakeFor(User $user, array $attributes = []): Topic
    {
        return Topic::factory()->mistake()->create(['user_id' => $user->id] + $attributes);
    }

    protected function tasksFor(Topic $topic, int $count = 1, array $attributes = []): void
    {
        StudyTask::factory()->count($count)->create(['topic_id' => $topic->id, 'user_id' => $topic->user_id] + $attributes);
    }

    protected function practiceLogFor(Topic $topic, array $attributes = []): PracticeLog
    {
        return PracticeLog::create([
            'user_id' => $topic->user_id,
            'topic_id' => $topic->id,
            'practiced_on' => today()->toDateString(),
            'practice_type' => 'problem_solving',
            'details' => 'Solved a problem.',
            'duration_minutes' => 30,
        ] + $attributes);
    }

    protected function categoryFor(User $user, array $attributes = []): Category
    {
        return Category::factory()->create(['user_id' => $user->id] + $attributes);
    }

    protected function scheduleFor(User $user, Category $category): CategoryReviewSchedule
    {
        return CategoryReviewSchedule::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'preset_key' => 'exam_short',
            'offsets' => [1, 3, 7, 14],
            'repeat_every_days' => 7,
        ]);
    }

    protected function templateFor(User $user, int $offset = 2, int $sequence = 1): TopicRevisionTemplate
    {
        return TopicRevisionTemplate::create([
            'user_id' => $user->id,
            'name' => "Revision {$sequence}",
            'day_offset' => $offset,
            'sequence_no' => $sequence,
            'is_active' => true,
        ]);
    }

    protected function snapshotFor(User $user, string $date, int $due = 3): ReviewLoadSnapshot
    {
        return ReviewLoadSnapshot::create([
            'user_id' => $user->id,
            'snapshot_date' => $date,
            'due_topics' => $due,
            'estimated_minutes' => $due * 5,
        ]);
    }

    protected function reportFor(User $user): EmailedStudyReport
    {
        return EmailedStudyReport::create([
            'user_id' => $user->id,
            'months' => [now()->format('Y-m')],
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /** An ended timer run of $minutes on the block. */
    protected function runFor(StudyBlock $block, int $minutes = 30): StudyBlockSession
    {
        return StudyBlockSession::factory()->create([
            'study_block_id' => $block->id,
            'user_id' => $block->user_id,
            'started_at' => now()->subMinutes($minutes),
            'used_seconds' => $minutes * 60,
            'ended_at' => now(),
        ]);
    }
}
