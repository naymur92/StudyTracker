<?php

namespace Tests\Feature\Study;

use App\Models\Topic;
use App\Services\IdHasher;

class TopicRecallCardTest extends StudyApiTestCase
{
    private function create(array $extra = [])
    {
        return $this->postJson('/api/study/topics', array_merge([
            'title' => 'pandas: groupby / agg / transform',
            'first_study_date' => today()->toDateString(),
        ], $extra));
    }

    public function test_recall_card_fields_are_saved_in_order(): void
    {
        $this->create([
            'recall_questions' => [
                ['question' => 'What does groupby return?'],
                ['question' => 'agg vs transform?', 'answer' => 'agg reduces, transform keeps shape'],
                ['question' => 'How to name output columns?'],
            ],
            'summary' => "Line one\nLine two",
            'practice_prompt' => 'Re-implement groupby/agg on a toy dataset',
            'lane' => 'work',
        ])
            ->assertCreated()
            ->assertJsonPath('data.recall_questions.0.question', 'What does groupby return?')
            ->assertJsonPath('data.recall_questions.0.answer', null)
            ->assertJsonPath('data.recall_questions.1.answer', 'agg reduces, transform keeps shape')
            ->assertJsonPath('data.recall_questions.2.question', 'How to name output columns?')
            ->assertJsonPath('data.summary', "Line one\nLine two")
            ->assertJsonPath('data.practice_prompt', 'Re-implement groupby/agg on a toy dataset')
            ->assertJsonPath('data.lane', 'work')
            ->assertJsonPath('data.kind', 'topic');
    }

    public function test_too_many_questions_rejected(): void
    {
        $questions = array_fill(0, 11, ['question' => 'Q?']);

        $this->create(['recall_questions' => $questions])->assertStatus(422)->assertJsonValidationErrors('recall_questions', 'errors');
    }

    public function test_empty_question_names_the_item(): void
    {
        $this->create(['recall_questions' => [['question' => 'Fine?'], ['question' => '  ']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('recall_questions.1.question', 'errors');
    }

    public function test_invalid_lane_rejected(): void
    {
        $this->create(['lane' => 'review'])->assertStatus(422)->assertJsonValidationErrors('lane', 'errors');
    }

    public function test_update_saves_recall_card(): void
    {
        $topic = Topic::factory()->create(['user_id' => $this->user->id]);

        $this->putJson('/api/study/topics/'.IdHasher::encode($topic->id), [
            'title' => $topic->title,
            'recall_questions' => [['question' => 'Why?', 'answer' => 'Because']],
            'lane' => 'minor',
        ])->assertOk()->assertJsonPath('data.recall_questions.0.answer', 'Because')->assertJsonPath('data.lane', 'minor');
    }

    public function test_default_list_hides_mistakes_and_filters_by_lane(): void
    {
        Topic::factory()->count(5)->create(['user_id' => $this->user->id, 'lane' => 'major']);
        Topic::factory()->count(2)->create(['user_id' => $this->user->id, 'lane' => 'minor']);
        Topic::factory()->mistake()->count(3)->create(['user_id' => $this->user->id]);

        $this->assertCount(7, $this->getJson('/api/study/topics?per_page=50')->json('data'));
        $this->assertCount(2, $this->getJson('/api/study/topics?lane=minor')->json('data'));
        $this->assertCount(3, $this->getJson('/api/study/topics?kind=mistake')->json('data'));
        $this->assertCount(10, $this->getJson('/api/study/topics?kind=all&per_page=50')->json('data'));
    }
}
