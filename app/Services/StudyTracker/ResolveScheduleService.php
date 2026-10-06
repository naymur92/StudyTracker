<?php

namespace App\Services\StudyTracker;

use App\Models\CategoryReviewSchedule;
use App\Models\Topic;
use App\Models\TopicRevisionTemplate;
use App\Services\StudyTracker\Scheduling\Schedule;

/**
 * Resolves which review schedule a new topic gets.
 *
 * Order: mistakes preset (mistake entries) → the user's category schedule →
 * the user's / system revision templates → built-in [1, 7, 30, 90].
 */
class ResolveScheduleService
{
    public function forNewTopic(int $userId, ?int $categoryId, string $kind = Topic::KIND_TOPIC): Schedule
    {
        if ($kind === Topic::KIND_MISTAKE) {
            $preset = config('study.schedule_presets.mistakes');

            return new Schedule($preset['offsets'], $preset['repeat_every_days'], null, 'preset:mistakes');
        }

        if ($categoryId !== null) {
            $categorySchedule = CategoryReviewSchedule::where('user_id', $userId)
                ->where('category_id', $categoryId)
                ->first();

            if ($categorySchedule) {
                return new Schedule(
                    $categorySchedule->offsets,
                    $categorySchedule->repeat_every_days,
                    $categorySchedule->repeat_until,
                    'category',
                );
            }
        }

        $templates = TopicRevisionTemplate::getForUser($userId)->sortBy('sequence_no')->values();

        if ($templates->isNotEmpty()) {
            $source = $templates->first()->user_id ? 'user_default' : 'system_default';

            return new Schedule(
                $templates->pluck('day_offset')->map(fn ($v) => (int) $v)->all(),
                null,
                null,
                $source,
                $templates->map(fn ($t) => trim((string) $t->name))->all(),
            );
        }

        return new Schedule(config('study.builtin_offsets', [1, 7, 30, 90]), null, null, 'builtin');
    }

    /** Store a resolved schedule on a topic (snapshot columns only, not saved). */
    public function applySnapshot(Topic $topic, Schedule $schedule): void
    {
        $topic->srs_offsets = $schedule->offsets;
        $topic->srs_repeat_every_days = $schedule->repeatEveryDays;
        $topic->srs_repeat_until = $schedule->repeatUntil?->toDateString();
        $topic->srs_schedule_source = $schedule->source;
    }
}
