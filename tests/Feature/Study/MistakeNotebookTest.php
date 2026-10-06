<?php

namespace Tests\Feature\Study;

use App\Models\Category;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Models\User;
use App\Services\IdHasher;
use Illuminate\Support\Carbon;

class MistakeNotebookTest extends StudyApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-11 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function log(array $extra = [])
    {
        return $this->postJson('/api/study/mistakes', array_merge([
            'question' => "T/F/NG: 'Most respondents…'",
            'correct_answer' => 'Not Given',
            'cause' => 'concept',
        ], $extra));
    }

    private function id($response): Topic
    {
        return Topic::findOrFail(IdHasher::decode($response->json('data.id')));
    }

    /** A mistake whose reviews are all done (state "ready"). */
    private function readyMistake(?Topic $parent = null, string $question = 'Q?'): Topic
    {
        $mistake = $this->id($this->log(['question' => $question, 'parent_topic_id' => $parent ? IdHasher::encode($parent->id) : null]));
        StudyTask::where('topic_id', $mistake->id)->update(['status' => 'completed', 'completed_at' => now(), 'is_date_locked' => true]);

        return $mistake;
    }

    private function merge(Topic $mistake)
    {
        return $this->postJson('/api/study/mistakes/'.IdHasher::encode($mistake->id).'/merge');
    }

    public function test_log_a_mistake_with_parent_context(): void
    {
        $ielts = Category::factory()->create(['user_id' => $this->user->id]);
        $parent = Topic::factory()->create(['user_id' => $this->user->id, 'category_id' => $ielts->id, 'lane' => 'minor', 'title' => 'IELTS Reading']);

        $response = $this->log(['my_answer' => 'False', 'source' => 'IELTS mock 3', 'parent_topic_id' => IdHasher::encode($parent->id)])
            ->assertCreated()
            ->assertJsonPath('data.question', "T/F/NG: 'Most respondents…'")
            ->assertJsonPath('data.my_answer', 'False')
            ->assertJsonPath('data.source', 'IELTS mock 3')
            ->assertJsonPath('data.lane', 'minor')
            ->assertJsonPath('data.state', 'active')
            ->assertJsonPath('data.next_review_date', '2026-10-12')
            ->assertJsonPath('data.parent_topic.title', 'IELTS Reading')
            ->assertJsonPath('data.category.name', $ielts->name);

        $mistake = $this->id($response);
        $this->assertSame('mistake', $mistake->kind);
        $this->assertEquals([['question' => "T/F/NG: 'Most respondents…'", 'answer' => 'Not Given']], $mistake->recall_questions);
        $dates = StudyTask::where('topic_id', $mistake->id)->orderBy('scheduled_date')->get();
        $this->assertSame(['2026-10-12', '2026-10-14', '2026-10-18'], $dates->map(fn ($t) => $t->scheduled_date->toDateString())->all());
        $this->assertSame(0, StudyTask::where('topic_id', $mistake->id)->where('task_type', 'learn')->count());
    }

    public function test_validation(): void
    {
        $this->log(['cause' => null])->assertStatus(422)->assertJsonValidationErrors('cause', 'errors');
        $foreign = Topic::factory()->create(['user_id' => User::factory()->create()->id]);
        $this->log(['parent_topic_id' => IdHasher::encode($foreign->id)])->assertStatus(422)->assertJsonValidationErrors('parent_topic_id', 'errors');
        $this->log(['logged_on' => '2026-10-12'])->assertStatus(422)->assertJsonValidationErrors('logged_on', 'errors');
        $this->assertSame(0, Topic::mistakeEntries()->count());
    }

    public function test_demo_user_cannot_log(): void
    {
        $this->actingAsDemo();
        $this->log()->assertForbidden();
    }

    public function test_failed_mistake_review_restarts_schedule(): void
    {
        $mistake = $this->id($this->log());
        Carbon::setTestNow('2026-10-12 09:00:00');
        $first = StudyTask::where('topic_id', $mistake->id)->orderBy('scheduled_date')->first();

        $this->postJson('/api/study/tasks/'.IdHasher::encode($first->id).'/complete', ['recall_grade' => 'again'])
            ->assertOk()->assertJsonPath('data.schedule_outcome.next_review_date', '2026-10-13');
    }

    public function test_states_and_list_filters(): void
    {
        $this->log(['question' => 'Active 1', 'cause' => 'careless']);
        $this->log(['question' => 'Active 2']);
        $this->readyMistake(null, 'Ready one');
        $closed = $this->readyMistake(null, 'Closed one');
        $this->merge($closed)->assertOk();

        $this->assertCount(3, $this->getJson('/api/study/mistakes')->assertOk()->json('data'));
        $this->assertCount(2, $this->getJson('/api/study/mistakes?state=active')->json('data'));
        $this->assertSame('Ready one', $this->getJson('/api/study/mistakes?state=ready')->json('data.0.question'));
        $this->assertSame('closed', $this->getJson('/api/study/mistakes?state=closed')->json('data.0.state'));
        $this->assertSame('Active 1', $this->getJson('/api/study/mistakes?cause=careless')->json('data.0.question'));
        $this->assertCount(4, $this->getJson('/api/study/mistakes?state=all')->json('data'));
    }

    public function test_graduated_mistake_becomes_ready(): void
    {
        $mistake = $this->id($this->log());
        $tasks = StudyTask::where('topic_id', $mistake->id)->orderBy('scheduled_date')->get();

        foreach ($tasks as $task) {
            Carbon::setTestNow($task->scheduled_date->copy()->setHour(9));
            $this->postJson('/api/study/tasks/'.IdHasher::encode($task->id).'/complete', ['recall_grade' => 'good'])->assertOk();
        }

        $this->assertSame('ready', $this->getJson('/api/study/mistakes?state=all')->json('data.0.state'));
    }

    public function test_update_and_delete(): void
    {
        $mistake = $this->id($this->log());
        $url = '/api/study/mistakes/'.IdHasher::encode($mistake->id);

        $this->patchJson($url, ['correct_answer' => 'Not Given (no claim in text)'])
            ->assertOk()->assertJsonPath('data.correct_answer', 'Not Given (no claim in text)');
        $this->assertSame('Not Given (no claim in text)', $mistake->fresh()->recall_questions[0]['answer']);

        $regular = Topic::factory()->create(['user_id' => $this->user->id]);
        $this->patchJson('/api/study/mistakes/'.IdHasher::encode($regular->id), ['cause' => 'memory'])->assertNotFound();

        $this->deleteJson($url)->assertOk();
        $this->assertSame(0, $this->getJson('/api/study/review-queue?date=2026-10-12')->json('data.summary.due_topics'));
    }

    public function test_merge_into_parent(): void
    {
        $parent = Topic::factory()->create(['user_id' => $this->user->id, 'recall_questions' => array_fill(0, 4, ['question' => 'Existing?', 'answer' => null])]);
        $mistake = $this->readyMistake($parent, 'New question?');

        $this->merge($mistake)->assertOk()->assertJsonPath('data.state', 'closed')->assertJsonPath('data.appended_to_parent', true);

        $questions = $parent->fresh()->recall_questions;
        $this->assertCount(5, $questions);
        $this->assertEquals(['question' => 'New question?', 'answer' => 'Not Given'], end($questions));
    }

    public function test_merge_rules(): void
    {
        $active = $this->id($this->log());
        $this->merge($active)->assertStatus(422);
        $this->assertNull($active->fresh()->merged_at);

        $duplicateParent = Topic::factory()->create(['user_id' => $this->user->id, 'recall_questions' => [['question' => '  new QUESTION? ', 'answer' => null]]]);
        $this->merge($this->readyMistake($duplicateParent, 'New question?'))->assertOk()->assertJsonPath('data.appended_to_parent', false);
        $this->assertCount(1, $duplicateParent->fresh()->recall_questions);

        $fullParent = Topic::factory()->create(['user_id' => $this->user->id, 'recall_questions' => array_map(fn ($i) => ['question' => "Q{$i}", 'answer' => null], range(1, 10))]);
        $full = $this->readyMistake($fullParent, 'Eleventh?');
        $this->merge($full)->assertStatus(422);
        $this->assertNull($full->fresh()->merged_at);

        $orphan = $this->readyMistake(null, 'Orphan?');
        $this->merge($orphan)->assertOk()->assertJsonPath('data.state', 'closed')->assertJsonPath('data.appended_to_parent', false);
    }

    public function test_foreign_mistake_is_forbidden(): void
    {
        $other = User::factory()->create();
        $mistake = Topic::factory()->mistake()->create(['user_id' => $other->id]);

        $this->patchJson('/api/study/mistakes/'.IdHasher::encode($mistake->id), ['cause' => 'memory'])->assertForbidden();
    }
}
