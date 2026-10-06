<?php

namespace Tests\Feature\Study;

class StudyPreferencesApiTest extends StudyApiTestCase
{
    public function test_new_user_gets_defaults(): void
    {
        $this->getJson('/api/study/preferences')
            ->assertOk()
            ->assertJsonPath('data.review_budget_minutes', 25)
            ->assertJsonPath('data.week_starts_on', 0)
            ->assertJsonPath('data.off_days', [5, 6]);
    }

    public function test_partial_update_keeps_other_values(): void
    {
        $this->putJson('/api/study/preferences', ['weekly_new_topic_cap' => 5])->assertOk();

        $this->putJson('/api/study/preferences', ['review_budget_minutes' => 20])
            ->assertOk()
            ->assertJsonPath('data.review_budget_minutes', 20)
            ->assertJsonPath('data.weekly_new_topic_cap', 5)
            ->assertJsonPath('data.review_debt_threshold_minutes', 30);
    }

    public function test_threshold_below_budget_is_rejected(): void
    {
        $this->putJson('/api/study/preferences', ['review_debt_threshold_minutes' => 20])
            ->assertStatus(422)
            ->assertJsonPath('flag', false);

        $this->assertNull($this->user->fresh()->study_preferences);
    }

    public function test_invalid_off_day_is_rejected(): void
    {
        $this->putJson('/api/study/preferences', ['off_days' => [5, 9]])->assertStatus(422);
    }

    public function test_demo_user_cannot_update(): void
    {
        $demo = $this->actingAsDemo();

        $this->putJson('/api/study/preferences', ['review_budget_minutes' => 20])->assertForbidden();
        $this->assertNull($demo->fresh()->study_preferences);
    }

    public function test_study_profile_defaults_saves_and_validates(): void
    {
        $this->getJson('/api/study/preferences')->assertJsonPath('data.study_profile', 'job_holder');

        $this->putJson('/api/study/preferences', ['study_profile' => 'student'])
            ->assertOk()
            ->assertJsonPath('data.study_profile', 'student');
        $this->getJson('/api/study/preferences')->assertJsonPath('data.study_profile', 'student');

        $this->putJson('/api/study/preferences', ['study_profile' => 'teacher'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('study_profile', 'errors');
    }
}
