<?php

namespace App\Services\StudyTracker;

use App\Models\ReviewLoadSnapshot;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Daily review load: due-today estimate, budget, review debt, weekly new-topic
 * cap and the "never miss twice" prompt.
 */
class ReviewLoadService
{
    /** Due revision tasks (pending/missed) on or before $date, of live, non-archived topics. */
    public static function dueTasksQuery(int $userId, CarbonInterface $date): Builder
    {
        return StudyTask::query()
            ->where('study_tasks.user_id', $userId)
            ->where('task_type', 'revision')
            ->whereIn('status', ['pending', 'missed'])
            ->whereDate('scheduled_date', '<=', $date->toDateString())
            ->whereHas('topic', fn ($q) => $q->where('status', '!=', 'archived'));
    }

    public function forDate(User $user, ?CarbonInterface $date = null): array
    {
        $date = Carbon::parse($date ?? today())->startOfDay();
        $prefs = StudyPreferences::for($user);

        $dueTopics = (clone self::dueTasksQuery($user->id, $date))->distinct()->count('topic_id');
        $overdueTopics = (clone self::dueTasksQuery($user->id, $date))
            ->whereDate('scheduled_date', '<', $date->toDateString())
            ->distinct()
            ->count('topic_id');

        $perReview = $this->perReviewMinutes($user, $prefs);
        $estimated = (int) ceil(round($dueTopics * $perReview, 6));
        $budget = $prefs->reviewBudgetMinutes();

        if ($date->isToday()) {
            $this->recordSnapshot($user->id, $date, $dueTopics, $estimated, false);
        }

        [$debtActive, $debtSince] = $this->reviewDebt($user->id, $date, $estimated, $prefs);

        return [
            'date' => $date->toDateString(),
            'due_topics' => $dueTopics,
            'overdue_topics' => $overdueTopics,
            'per_review_minutes' => $perReview,
            'estimated_minutes' => $estimated,
            'budget_minutes' => $budget,
            'over_budget' => $estimated > $budget,
            'debt_threshold_minutes' => $prefs->reviewDebtThresholdMinutes(),
            'review_debt_active' => $debtActive,
            'review_debt_since' => $debtSince,
            'new_topics_this_week' => $this->newTopicsThisWeek($user->id, $date, $prefs),
            'weekly_new_topic_cap' => $prefs->weeklyNewTopicCap(),
            'never_miss_twice' => $this->neverMissTwice($user->id, $date),
        ];
    }

    /**
     * Mean duration of the user's recent timed, graded reviews (minutes), or the
     * user's preference when there are fewer than the minimum samples.
     */
    public function perReviewMinutes(User $user, ?StudyPreferences $prefs = null): float
    {
        $prefs ??= StudyPreferences::for($user);
        $config = config('study.review');

        $seconds = StudyTask::where('user_id', $user->id)
            ->where('task_type', 'revision')
            ->whereNotNull('recall_grade')
            ->whereNotNull('review_seconds')
            ->orderByDesc('completed_at')
            ->limit($config['timing_sample_size'])
            ->pluck('review_seconds');

        if ($seconds->count() < $config['timing_min_samples']) {
            return $prefs->minutesPerReview();
        }

        $minutes = $seconds->avg() / 60;

        return round(max($config['per_review_min_clamp'], min($config['per_review_max_clamp'], $minutes)), 2);
    }

    /** Record (or refresh) the start-of-day load snapshot. */
    public function recordSnapshot(int $userId, CarbonInterface $date, int $dueTopics, int $estimated, bool $overwrite = true): void
    {
        // whereDate: a date cast is stored as "Y-m-d 00:00:00" on SQLite.
        $existing = ReviewLoadSnapshot::where('user_id', $userId)
            ->whereDate('snapshot_date', $date->toDateString())
            ->first();

        if ($existing && ! $overwrite) {
            return;
        }

        $values = ['due_topics' => $dueTopics, 'estimated_minutes' => $estimated];

        if ($existing) {
            $existing->update($values);
        } else {
            ReviewLoadSnapshot::create(['user_id' => $userId, 'snapshot_date' => $date->toDateString()] + $values);
        }
    }

    /** Snapshot the current load of a user (used by the daily command). */
    public function snapshot(User $user, ?CarbonInterface $date = null): void
    {
        $date = Carbon::parse($date ?? today())->startOfDay();
        $due = (clone self::dueTasksQuery($user->id, $date))->distinct()->count('topic_id');

        $this->recordSnapshot($user->id, $date, $due, (int) ceil(round($due * $this->perReviewMinutes($user), 6)));
    }

    /**
     * Debt starts after three consecutive days above the threshold and lasts
     * until a later day (or today's live estimate) drops below the budget.
     *
     * @return array{0: bool, 1: ?string}
     */
    private function reviewDebt(int $userId, Carbon $date, int $liveEstimate, StudyPreferences $prefs): array
    {
        $threshold = $prefs->reviewDebtThresholdMinutes();
        $budget = $prefs->reviewBudgetMinutes();
        $lookback = (int) config('study.review.debt_lookback_days', 15);

        $snapshots = ReviewLoadSnapshot::where('user_id', $userId)
            ->whereDate('snapshot_date', '>=', $date->copy()->subDays($lookback - 1)->toDateString())
            ->whereDate('snapshot_date', '<=', $date->toDateString())
            ->get()
            ->mapWithKeys(fn ($s) => [$s->snapshot_date->toDateString() => $s->estimated_minutes]);

        $heavy = fn (Carbon $day) => ($snapshots[$day->toDateString()] ?? -1) > $threshold;

        // Latest day that ends a run of three consecutive heavy days.
        $runEnd = null;
        for ($day = $date->copy(); $day->gte($date->copy()->subDays($lookback - 3)); $day->subDay()) {
            if ($heavy($day) && $heavy($day->copy()->subDay()) && $heavy($day->copy()->subDays(2))) {
                $runEnd = $day->copy();
                break;
            }
        }

        if ($runEnd === null) {
            return [false, null];
        }

        for ($day = $runEnd->copy()->addDay(); $day->lt($date); $day->addDay()) {
            $value = $snapshots[$day->toDateString()] ?? null;
            if ($value !== null && $value < $budget) {
                return [false, null];
            }
        }

        if ($liveEstimate < $budget) {
            return [false, null];
        }

        return [true, $runEnd->copy()->subDays(2)->toDateString()];
    }

    private function newTopicsThisWeek(int $userId, Carbon $date, StudyPreferences $prefs): int
    {
        $weekStart = WeekMath::weekStart($date, $prefs->weekStartsOn());

        return Topic::where('user_id', $userId)
            ->regular()
            ->where('created_at', '>=', $weekStart->copy()->startOfDay())
            ->where('created_at', '<', $date->copy()->addDay()->startOfDay())
            ->count();
    }

    private function neverMissTwice(int $userId, Carbon $date): bool
    {
        $yesterday = $date->copy()->subDay();

        $reviewedYesterday = StudyTask::where('user_id', $userId)
            ->where('task_type', 'revision')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$yesterday->copy()->startOfDay(), $yesterday->copy()->endOfDay()])
            ->exists();

        if ($reviewedYesterday) {
            return false;
        }

        return self::dueTasksQuery($userId, $yesterday)->exists();
    }
}
