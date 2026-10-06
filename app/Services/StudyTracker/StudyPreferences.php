<?php

namespace App\Services\StudyTracker;

use App\Models\User;

/**
 * A user's study preferences: saved values merged over config defaults.
 */
class StudyPreferences
{
    public function __construct(private array $values) {}

    public static function defaults(): array
    {
        return config('study.preference_defaults');
    }

    public static function for(User $user): self
    {
        $saved = is_array($user->study_preferences) ? $user->study_preferences : [];

        return new self(array_merge(self::defaults(), array_intersect_key($saved, self::defaults())));
    }

    /** Merge new values into the user's saved preferences and persist them. */
    public static function update(User $user, array $values): self
    {
        $saved = is_array($user->study_preferences) ? $user->study_preferences : [];
        $user->study_preferences = array_merge($saved, array_intersect_key($values, self::defaults()));
        $user->save();

        return self::for($user);
    }

    public function all(): array
    {
        return $this->values;
    }

    public function reviewBudgetMinutes(): int
    {
        return (int) $this->values['review_budget_minutes'];
    }

    public function reviewDebtThresholdMinutes(): int
    {
        return (int) $this->values['review_debt_threshold_minutes'];
    }

    public function minutesPerReview(): float
    {
        return (float) $this->values['minutes_per_review'];
    }

    public function weeklyNewTopicCap(): int
    {
        return (int) $this->values['weekly_new_topic_cap'];
    }

    public function weekStartsOn(): int
    {
        return (int) $this->values['week_starts_on'];
    }

    /** @return int[] */
    public function offDays(): array
    {
        return array_map('intval', (array) $this->values['off_days']);
    }

    /** `job_holder` or `student`: selects the weekly gear templates. */
    public function studyProfile(): string
    {
        $profile = (string) $this->values['study_profile'];

        return in_array($profile, config('study.study_profiles'), true) ? $profile : 'job_holder';
    }

    public function isStudent(): bool
    {
        return $this->studyProfile() === 'student';
    }

    public function successThresholdPercent(): int
    {
        return (int) $this->values['success_threshold_percent'];
    }
}
