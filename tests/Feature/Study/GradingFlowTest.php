<?php

namespace Tests\Feature\Study;

use App\Models\Category;
use App\Models\CategoryReviewSchedule;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Services\IdHasher;
use Illuminate\Support\Carbon;

class GradingFlowTest extends StudyApiTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * A topic with offsets [1, 7, 30, 90] and the given revisions:
     * [revision_no => [date, status]].
     */
    private function topicWithRevisions(int $step, array $revisions, string $firstStudy = '2026-10-07'): Topic
    {
        $topic = Topic::factory()->withSchedule([1, 7, 30, 90])->create([
            'user_id' => $this->user->id,
            'srs_step' => $step,
            'first_study_date' => $firstStudy,
            'title' => 'Pandas groupby',
        ]);

        foreach ($revisions as $no => [$date, $status]) {
            $factory = StudyTask::factory()->for($topic)->revision($no);
            $factory = $status === 'completed' ? $factory->completed() : $factory->state(['status' => $status]);
            $factory->create(['user_id' => $this->user->id, 'scheduled_date' => $date, 'review_kind' => 'step']);
        }

        return $topic;
    }

    private function task(Topic $topic, int $revisionNo): StudyTask
    {
        return StudyTask::where('topic_id', $topic->id)->where('revision_no', $revisionNo)->firstOrFail();
    }

    private function complete(StudyTask $task, array $body = [])
    {
        return $this->postJson('/api/study/tasks/'.IdHasher::encode($task->id).'/complete', $body);
    }

    /** @return string[] */
    private function pendingDates(Topic $topic): array
    {
        return StudyTask::where('topic_id', $topic->id)
            ->where('task_type', 'revision')
            ->whereIn('status', ['pending', 'missed'])
            ->orderBy('scheduled_date')
            ->get()
            ->map(fn ($t) => $t->scheduled_date->toDateString())
            ->all();
    }

    public function test_good_on_second_step_replans_from_review_date(): void
    {
        Carbon::setTestNow('2026-10-14 09:00:00');
        $topic = $this->topicWithRevisions(1, [
            1 => ['2026-10-08', 'completed'],
            2 => ['2026-10-14', 'pending'],
            3 => ['2026-11-06', 'pending'],
            4 => ['2027-01-05', 'pending'],
        ]);

        $this->complete($this->task($topic, 2), ['recall_grade' => 'good', 'review_seconds' => 140])
            ->assertOk()
            ->assertJsonPath('data.recall_grade', 'good')
            ->assertJsonPath('data.review_seconds', 140)
            ->assertJsonPath('data.schedule_outcome.next_review_date', '2026-11-06')
            ->assertJsonPath('data.schedule_outcome.srs_step', 2)
            ->assertJsonPath('data.schedule_outcome.graduated', false);

        $this->assertSame(['2026-11-06', '2027-01-05'], $this->pendingDates($topic));
        $this->assertSame(2, $topic->fresh()->srs_step);
        $this->assertSame('2026-10-14', $topic->fresh()->last_reviewed_on->toDateString());
    }

    public function test_late_review_anchors_on_actual_day(): void
    {
        Carbon::setTestNow('2026-10-13 09:00:00');
        $topic = $this->topicWithRevisions(0, [1 => ['2026-10-10', 'missed'], 2 => ['2026-10-16', 'pending']]);

        $this->complete($this->task($topic, 1), ['recall_grade' => 'good'])->assertOk();

        // step 1 passed on 10-13 → next = 10-13 + (7 − 1)
        $this->assertSame('2026-10-19', $this->pendingDates($topic)[0]);
    }

    public function test_again_expands_the_remaining_plan_and_keeps_history(): void
    {
        Carbon::setTestNow('2026-11-06 09:00:00');
        $topic = $this->topicWithRevisions(2, [
            1 => ['2026-10-08', 'completed'],
            2 => ['2026-10-14', 'completed'],
            3 => ['2026-11-06', 'pending'],
            4 => ['2027-01-05', 'pending'],
        ]);
        $history = StudyTask::where('topic_id', $topic->id)->where('status', 'completed')->get()
            ->mapWithKeys(fn ($t) => [$t->id => $t->scheduled_date->toDateString()]);

        $this->complete($this->task($topic, 3), ['recall_grade' => 'again'])
            ->assertOk()
            ->assertJsonPath('data.schedule_outcome.next_review_date', '2026-11-07')
            ->assertJsonPath('data.schedule_outcome.next_review_kind', 'relearn');

        $this->assertSame(['2026-11-07', '2026-11-13', '2026-12-06', '2027-02-04'], $this->pendingDates($topic));
        $this->assertSame(0, $topic->fresh()->srs_step);
        $this->assertSame(1, $topic->fresh()->srs_lapses);

        // Numbering continues after the highest completed revision (3, just graded).
        $pending = StudyTask::where('topic_id', $topic->id)->where('status', 'pending')->orderBy('scheduled_date')->get();
        $this->assertSame([4, 5, 6, 7], $pending->pluck('revision_no')->all());
        $this->assertSame(['relearn', 'step', 'step', 'step'], $pending->pluck('review_kind')->all());
        $this->assertStringStartsWith('Relearn check', $pending->first()->title);

        foreach ($history as $id => $date) {
            $fresh = StudyTask::find($id);
            $this->assertSame('completed', $fresh->status);
            $this->assertSame($date, $fresh->scheduled_date->toDateString());
        }
    }

    public function test_hard_schedules_relearn_check_labelled_as_such(): void
    {
        Carbon::setTestNow('2026-11-06 09:00:00');
        $topic = $this->topicWithRevisions(2, [3 => ['2026-11-06', 'pending'], 4 => ['2027-01-05', 'pending']]);

        $this->complete($this->task($topic, 3), ['recall_grade' => 'hard'])->assertOk();

        $relearn = StudyTask::where('topic_id', $topic->id)->where('status', 'pending')->orderBy('scheduled_date')->first();
        $this->assertSame('2026-11-07', $relearn->scheduled_date->toDateString());
        $this->assertSame('relearn', $relearn->review_kind);
        $this->assertStringStartsWith('Relearn check', $relearn->taskTypeLabel());
        $this->assertSame(2, $topic->fresh()->srs_step);
    }

    public function test_backlog_collapses_after_a_review(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');
        $topic = $this->topicWithRevisions(1, [
            1 => ['2026-10-08', 'completed'],
            2 => ['2026-10-14', 'missed'],
            3 => ['2026-10-18', 'missed'],
        ]);

        $this->complete($this->task($topic, 2), ['recall_grade' => 'good'])->assertOk();

        $revision3 = StudyTask::where('topic_id', $topic->id)->where('status', 'pending')->first();
        $this->assertNotNull($revision3);
        $this->assertTrue($revision3->scheduled_date->gt(today()));
        $this->assertSame(0, StudyTask::where('topic_id', $topic->id)
            ->whereIn('status', ['pending', 'missed'])->where('scheduled_date', '<', today())->count());
    }

    public function test_graduation_leaves_no_pending_reviews(): void
    {
        Carbon::setTestNow('2027-01-05 09:00:00');
        $topic = $this->topicWithRevisions(3, [4 => ['2027-01-05', 'pending']]);

        $this->complete($this->task($topic, 4), ['recall_grade' => 'good'])
            ->assertOk()
            ->assertJsonPath('data.schedule_outcome.graduated', true)
            ->assertJsonPath('data.schedule_outcome.next_review_date', null);

        $this->assertSame([], $this->pendingDates($topic));
    }

    public function test_ungraded_completion_keeps_fixed_dates(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');
        $topic = $this->topicWithRevisions(1, [2 => ['2026-10-14', 'missed'], 3 => ['2026-11-06', 'pending']]);

        $this->complete($this->task($topic, 2), ['difficulty_feedback' => 'medium'])
            ->assertOk()
            ->assertJsonPath('data.difficulty_feedback', 'medium')
            ->assertJsonPath('data.schedule_outcome', null);

        $this->assertSame(['2026-11-06'], $this->pendingDates($topic));
        $this->assertSame(2, $topic->fresh()->srs_step);
    }

    public function test_grade_rejected_for_learn_task_and_unknown_values(): void
    {
        $topic = Topic::factory()->create(['user_id' => $this->user->id]);
        $learn = StudyTask::factory()->for($topic)->learn()->create(['user_id' => $this->user->id]);
        $revision = StudyTask::factory()->for($topic)->revision(1)->create(['user_id' => $this->user->id]);

        $this->complete($learn, ['recall_grade' => 'easy'])->assertStatus(422);
        $this->complete($revision, ['recall_grade' => 'medium'])->assertStatus(422);
        $this->complete($revision, ['recall_grade' => 'good', 'review_seconds' => 0])->assertStatus(422);
        $this->complete($revision, ['recall_grade' => 'good', 'review_seconds' => 7200])->assertStatus(422);

        $this->assertSame('pending', $learn->fresh()->status);
        $this->assertSame('pending', $revision->fresh()->status);
    }

    public function test_double_submit_takes_effect_once(): void
    {
        Carbon::setTestNow('2026-11-06 09:00:00');
        $topic = $this->topicWithRevisions(2, [3 => ['2026-11-06', 'pending'], 4 => ['2027-01-05', 'pending']]);
        $task = $this->task($topic, 3);

        $this->complete($task, ['recall_grade' => 'again'])->assertOk();
        $this->complete($task, ['recall_grade' => 'again'])->assertStatus(422);

        $this->assertSame(1, $topic->fresh()->srs_lapses);
        $this->assertCount(4, $this->pendingDates($topic));
    }

    public function test_learn_completed_late_reanchors_the_plan(): void
    {
        Carbon::setTestNow('2026-10-07 08:00:00');
        $topicId = $this->postJson('/api/study/topics', ['title' => 'NumPy broadcasting', 'first_study_date' => '2026-10-07'])
            ->assertCreated()->json('data.id');
        $topic = Topic::findOrFail(IdHasher::decode($topicId));

        Carbon::setTestNow('2026-10-10 08:00:00');
        $learn = StudyTask::where('topic_id', $topic->id)->where('task_type', 'learn')->first();
        $this->complete($learn)->assertOk()->assertJsonPath('data.schedule_outcome.next_review_date', '2026-10-11');

        $this->assertSame(['2026-10-11', '2026-10-17', '2026-11-09', '2027-01-08'], $this->pendingDates($topic));
    }

    public function test_learn_completed_on_planned_day_keeps_dates(): void
    {
        Carbon::setTestNow('2026-10-07 08:00:00');
        $topicId = $this->postJson('/api/study/topics', ['title' => 'SQL windows', 'first_study_date' => '2026-10-07'])->json('data.id');
        $topic = Topic::findOrFail(IdHasher::decode($topicId));
        $before = StudyTask::where('topic_id', $topic->id)->where('task_type', 'revision')->orderBy('id')->get(['id', 'title', 'scheduled_date'])->toArray();

        $learn = StudyTask::where('topic_id', $topic->id)->where('task_type', 'learn')->first();
        $this->complete($learn)->assertOk()->assertJsonPath('data.schedule_outcome', null);

        $after = StudyTask::where('topic_id', $topic->id)->where('task_type', 'revision')->orderBy('id')->get(['id', 'title', 'scheduled_date'])->toArray();
        $this->assertSame($before, $after);
    }

    public function test_learn_completed_after_a_revision_changes_nothing(): void
    {
        Carbon::setTestNow('2026-10-12 08:00:00');
        $topic = $this->topicWithRevisions(1, [1 => ['2026-10-08', 'completed'], 2 => ['2026-10-14', 'pending']]);
        $learn = StudyTask::factory()->for($topic)->learn()->create(['user_id' => $this->user->id, 'scheduled_date' => '2026-10-07']);

        $this->complete($learn)->assertOk();

        $this->assertSame(['2026-10-14'], $this->pendingDates($topic));
    }

    public function test_default_topic_creation_matches_previous_behavior(): void
    {
        Carbon::setTestNow('2026-10-07 08:00:00');
        $topicId = $this->postJson('/api/study/topics', ['title' => 'Docker basics', 'first_study_date' => '2026-10-07'])->json('data.id');

        $revisions = StudyTask::where('topic_id', IdHasher::decode($topicId))->where('task_type', 'revision')->orderBy('revision_no')->get();

        $this->assertSame(['2026-10-08', '2026-10-14', '2026-11-06', '2027-01-05'], $revisions->map(fn ($t) => $t->scheduled_date->toDateString())->all());
        $this->assertSame(
            ['Revision 1: Docker basics', 'Revision 2: Docker basics', 'Revision 3: Docker basics', 'Revision 4: Docker basics'],
            $revisions->pluck('title')->all()
        );
        $this->assertSame([1, 2, 3, 4], $revisions->pluck('revision_no')->all());
    }

    public function test_exam_short_category_schedule_on_creation(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        CategoryReviewSchedule::create([
            'user_id' => $this->user->id, 'category_id' => $category->id, 'preset_key' => 'exam_short',
            'offsets' => [1, 3, 7, 14], 'repeat_every_days' => 7, 'repeat_until' => '2026-12-05',
        ]);

        $topicId = $this->postJson('/api/study/topics', [
            'title' => 'T/F/NG', 'first_study_date' => '2026-10-11', 'category_id' => IdHasher::encode($category->id),
        ])->assertCreated()->json('data.id');

        $revisions = StudyTask::where('topic_id', IdHasher::decode($topicId))->where('task_type', 'revision')->orderBy('scheduled_date')->get();
        $this->assertSame(
            ['2026-10-12', '2026-10-14', '2026-10-18', '2026-10-25', '2026-11-01'],
            $revisions->map(fn ($t) => $t->scheduled_date->toDateString())->all()
        );
        $this->assertSame('repeat', $revisions->last()->review_kind);
        $this->assertSame([1, 3, 7, 14], Topic::find(IdHasher::decode($topicId))->srs_offsets);
    }
}
