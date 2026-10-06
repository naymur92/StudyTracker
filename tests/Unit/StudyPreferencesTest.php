<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\StudyTracker\StudyPreferences;
use Tests\TestCase;

class StudyPreferencesTest extends TestCase
{
    public function test_user_without_saved_values_gets_every_default(): void
    {
        $prefs = StudyPreferences::for(new User);

        $this->assertSame([
            'review_budget_minutes' => 25,
            'review_debt_threshold_minutes' => 30,
            'minutes_per_review' => 3,
            'weekly_new_topic_cap' => 8,
            'week_starts_on' => 0,
            'off_days' => [5, 6],
            'success_threshold_percent' => 80,
            'study_profile' => 'job_holder',
        ], $prefs->all());
        $this->assertFalse($prefs->isStudent());
    }

    public function test_saved_values_override_defaults_and_unknown_keys_are_ignored(): void
    {
        $user = new User;
        $user->study_preferences = ['review_budget_minutes' => 20, 'bogus' => 1];

        $prefs = StudyPreferences::for($user);

        $this->assertSame(20, $prefs->reviewBudgetMinutes());
        $this->assertSame(30, $prefs->reviewDebtThresholdMinutes());
        $this->assertArrayNotHasKey('bogus', $prefs->all());
    }
}
