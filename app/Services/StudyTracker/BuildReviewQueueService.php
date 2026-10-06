<?php

namespace App\Services\StudyTracker;

use App\Models\StudyTask;
use App\Models\User;
use App\Services\IdHasher;
use App\Services\StudyTracker\Scheduling\RecallScheduler;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Builds the day's review queue: one review per topic, overdue first (oldest
 * first), today's reviews mixed round-robin across categories, split at the
 * user's review budget, with a next-date preview for every grade.
 */
class BuildReviewQueueService
{
    public function __construct(
        private ReviewLoadService $loadService,
        private TopicScheduleResolver $scheduleResolver,
        private RecallScheduler $scheduler,
    ) {}

    public function build(User $user, ?CarbonInterface $date = null): array
    {
        $date = Carbon::parse($date ?? today())->startOfDay();
        $prefs = StudyPreferences::for($user);

        $tasks = ReviewLoadService::dueTasksQuery($user->id, $date)
            ->with('topic.category', 'topic.parentTopic')
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->get()
            ->unique('topic_id')
            ->values();

        [$overdue, $dueToday] = $tasks->partition(fn (StudyTask $t) => $t->scheduled_date->lt($date));

        $ordered = $overdue->values()->concat($this->interleaveByCategory($dueToday->values()));

        $perReview = $this->loadService->perReviewMinutes($user, $prefs);
        $budget = $prefs->reviewBudgetMinutes();

        $items = $ordered->values()->map(function (StudyTask $task, int $i) use ($date, $perReview, $budget) {
            $cumulative = round(($i + 1) * $perReview, 2);

            return $this->formatItem($task, $date) + [
                'cumulative_minutes' => $cumulative,
                'within_budget' => $i === 0 || $cumulative <= $budget,
            ];
        });

        $estimated = (int) ceil(round($items->count() * $perReview, 6));

        return [
            'date' => $date->toDateString(),
            'summary' => [
                'due_topics' => $items->count(),
                'overdue_topics' => $overdue->count(),
                'per_review_minutes' => $perReview,
                'estimated_minutes' => $estimated,
                'budget_minutes' => $budget,
                'over_budget' => $estimated > $budget,
            ],
            'items' => $items->all(),
        ];
    }

    /** Round-robin across categories (alphabetical, uncategorized last), keeping date order inside each. */
    private function interleaveByCategory(Collection $tasks): Collection
    {
        $groups = $tasks
            ->groupBy(fn (StudyTask $t) => $t->topic->category?->name ?? "\u{10FFFF}")
            ->sortKeysUsing(fn ($a, $b) => strcasecmp($a, $b))
            ->map(fn ($g) => $g->values())
            ->values();

        $result = collect();
        $longest = $groups->max(fn ($g) => $g->count()) ?? 0;

        for ($round = 0; $round < $longest; $round++) {
            foreach ($groups as $group) {
                if ($group->has($round)) {
                    $result->push($group[$round]);
                }
            }
        }

        return $result;
    }

    private function formatItem(StudyTask $task, Carbon $date): array
    {
        $topic = $task->topic;
        [$schedule, $state] = $this->scheduleResolver->peek($topic);

        return [
            'task' => [
                'id' => IdHasher::encode($task->id),
                'title' => $task->title,
                'revision_no' => $task->revision_no,
                'review_kind' => $task->review_kind,
                'type_label' => $task->taskTypeLabel(),
                'scheduled_date' => $task->scheduled_date->toDateString(),
                'status' => $task->status,
                'is_overdue' => $task->scheduled_date->lt($date),
            ],
            'topic' => [
                'id' => IdHasher::encode($topic->id),
                'title' => $topic->title,
                'kind' => $topic->kind ?? 'topic',
                'lane' => $topic->lane,
                'recall_questions' => $topic->recall_questions ?? [],
                'summary' => $topic->summary,
                'practice_prompt' => $topic->practice_prompt,
                'source_link' => $topic->source_link,
                'srs_step' => $state->step,
                'srs_steps_total' => $schedule->stepCount(),
                'mistake_details' => $topic->isMistake() ? $topic->mistake_details : null,
                'parent_topic' => $topic->parentTopic ? [
                    'id' => IdHasher::encode($topic->parentTopic->id),
                    'title' => $topic->parentTopic->title,
                ] : null,
                'category' => $topic->category ? [
                    'name' => $topic->category->name,
                    'color' => $topic->category->color,
                ] : null,
            ],
            'grade_preview' => $this->scheduler->preview($schedule, $state, $date),
        ];
    }
}
