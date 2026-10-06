<?php

namespace Tests\Feature\Study;

use App\Models\StudyBlock;
use App\Models\StudyTask;
use App\Services\IdHasher;
use Illuminate\Support\Carbon;

/**
 * End-to-end walk through the learning system at API level:
 * category schedule → topic with recall card → learn → graded reviews via
 * the review queue → mistake log and merge → weekly plan and blocks.
 */
class LearningSystemSmokeTest extends StudyApiTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function grade(string $date, string $grade): array
    {
        Carbon::setTestNow("{$date} 20:00:00");
        $queue = $this->getJson('/api/study/review-queue')->assertOk()->json('data');
        $this->assertNotEmpty($queue['items'], "a review is due on {$date}");
        $item = $queue['items'][0];

        return $this->postJson("/api/study/tasks/{$item['task']['id']}/complete", ['recall_grade' => $grade, 'review_seconds' => 120])
            ->assertOk()->json('data.schedule_outcome') + ['preview' => $item['grade_preview'][$grade]];
    }

    public function test_full_learning_journey(): void
    {
        Carbon::setTestNow('2026-10-11 08:00:00');

        // Category with the exam preset until the test date.
        $categoryId = $this->postJson('/api/study/categories', ['name' => 'IELTS'])->assertCreated()->json('data.id');
        $this->putJson("/api/study/categories/{$categoryId}/schedule", ['preset_key' => 'exam_short', 'repeat_until' => '2026-12-05'])->assertOk();

        // Topic with a recall card.
        $topicId = $this->postJson('/api/study/topics', [
            'title' => 'IELTS Reading: True/False/Not Given',
            'category_id' => $categoryId,
            'first_study_date' => '2026-10-11',
            'recall_questions' => [['question' => 'When is the answer Not Given?', 'answer' => 'The text neither confirms nor contradicts it.']],
            'summary' => 'True = agrees; False = contradicts; Not Given = no information.',
            'lane' => 'major',
        ])->assertCreated()->assertJsonPath('data.next_review_date', '2026-10-12')->json('data.id');

        // Learn on the planned day: dates stay as generated (+1, +3, +7, +14, then weekly).
        $learn = StudyTask::where('topic_id', IdHasher::decode($topicId))->where('task_type', 'learn')->first();
        $this->postJson('/api/study/tasks/'.IdHasher::encode($learn->id).'/complete')->assertOk();

        // Good on 12 Oct → step 1 passed, next is the +3 step on 14 Oct; preview matched.
        $out = $this->grade('2026-10-12', 'good');
        $this->assertSame('2026-10-14', $out['next_review_date']);
        $this->assertSame($out['preview'], $out['next_review_date']);

        // Hard on 14 Oct → relearn check tomorrow.
        $out = $this->grade('2026-10-14', 'hard');
        $this->assertSame('2026-10-15', $out['next_review_date']);
        $this->assertSame('relearn', $out['next_review_kind']);

        // Good on the relearn check → step 2 passed, next gap 7 − 3 = 4 days.
        $this->assertSame('2026-10-19', $this->grade('2026-10-15', 'good')['next_review_date']);

        // Again on 19 Oct → restart, relearn on 20 Oct.
        $out = $this->grade('2026-10-19', 'again');
        $this->assertSame('2026-10-20', $out['next_review_date']);
        $this->assertSame(0, $out['srs_step']);

        $topic = $this->getJson("/api/study/topics/{$topicId}")->assertOk()->json('data');
        $this->assertSame(1, $topic['topic']['srs_lapses']);
        $this->assertSame('2026-10-20', $topic['topic']['next_review_date']);

        // Calendar shows the projected dates.
        $calendar = $this->getJson('/api/study/calendar?year=2026&month=10')->json('data.calendar');
        $this->assertArrayHasKey('2026-10-20', $calendar);

        // Log a mistake, finish its reviews, merge it into the topic.
        Carbon::setTestNow('2026-10-20 08:00:00');
        $mistakeId = $this->postJson('/api/study/mistakes', [
            'question' => "Passage 2, Q5: 'Most respondents agreed…'", 'my_answer' => 'False',
            'correct_answer' => 'Not Given', 'cause' => 'concept', 'parent_topic_id' => $topicId,
        ])->assertCreated()->json('data.id');
        foreach (['2026-10-21', '2026-10-23', '2026-10-27'] as $day) {
            Carbon::setTestNow("{$day} 21:00:00");
            $task = StudyTask::where('topic_id', IdHasher::decode($mistakeId))->where('status', 'pending')->orderBy('scheduled_date')->first();
            $this->assertSame($day, $task->scheduled_date->toDateString());
            $this->postJson('/api/study/tasks/'.IdHasher::encode($task->id).'/complete', ['recall_grade' => 'good'])->assertOk();
        }
        $this->postJson("/api/study/mistakes/{$mistakeId}/merge")->assertOk()->assertJsonPath('data.appended_to_parent', true);
        $this->assertCount(2, $this->getJson("/api/study/topics/{$topicId}")->json('data.topic.recall_questions'));

        // Plan the week, pre-decide a task, mark blocks, check the score.
        Carbon::setTestNow('2026-10-27 08:00:00'); // Tuesday; week starts Sunday 25 Oct
        $plan = $this->postJson('/api/study/weekly-plan', ['week_start' => '2026-10-25', 'gear' => 'green'])->assertCreated()->json('data');
        $this->assertCount(21, $plan['blocks']);
        StudyBlock::whereDate('block_date', '2026-10-25')->update(['status' => 'done']);
        StudyBlock::whereDate('block_date', '2026-10-26')->update(['status' => 'partial']);
        $score = $this->getJson('/api/study/weekly-plan')->json('data.score');
        $this->assertSame(6, $score['counted']);
        $this->assertSame(75, $score['percent']);
        $this->assertFalse($score['on_track']);

        // Dashboard brings it together.
        $this->getJson('/api/study/dashboard')->assertOk()->assertJsonStructure(['data' => ['stats' => ['review_load' => ['due_topics', 'estimated_minutes', 'never_miss_twice']]]]);
    }
}
