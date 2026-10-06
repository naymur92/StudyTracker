<?php

namespace App\Services\StudyTracker;

use App\Models\StudyBlock;
use App\Models\StudyTask;
use App\Models\StudyWeek;
use App\Models\Topic;
use App\Models\User;
use App\Services\IdHasher;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Weekly plans: a gear (green / yellow / red), blocks generated from gear
 * templates and the user's off days, and a consistency score against the
 * user's success line.
 */
class WeeklyPlanService
{
    public function weekStart(CarbonInterface $date, StudyPreferences $prefs): Carbon
    {
        return WeekMath::weekStart($date, $prefs->weekStartsOn());
    }

    /** The user's plan whose week contains $date (latest start wins). */
    public function findWeek(int $userId, CarbonInterface $date): ?StudyWeek
    {
        $date = Carbon::parse($date)->startOfDay();

        return StudyWeek::where('user_id', $userId)
            ->whereDate('week_start', '<=', $date->toDateString())
            ->whereDate('week_start', '>=', $date->copy()->subDays(6)->toDateString())
            ->orderByDesc('week_start')
            ->first();
    }

    public function overlaps(int $userId, CarbonInterface $weekStart): bool
    {
        $start = Carbon::parse($weekStart)->startOfDay();

        return StudyWeek::where('user_id', $userId)
            ->whereDate('week_start', '>', $start->copy()->subDays(7)->toDateString())
            ->whereDate('week_start', '<', $start->copy()->addDays(7)->toDateString())
            ->exists();
    }

    public function create(User $user, array $data): StudyWeek
    {
        return DB::transaction(function () use ($user, $data) {
            $week = StudyWeek::create([
                'user_id' => $user->id,
                'week_start' => $data['week_start'],
                'gear' => $data['gear'],
                'major_focus' => $data['major_focus'] ?? null,
                'minor_focus' => $data['minor_focus'] ?? null,
            ]);

            if ($data['generate_blocks'] ?? true) {
                $this->generateBlocks($week, $data['gear'], StudyPreferences::for($user));
            }

            return $week;
        });
    }

    /**
     * Create the gear template's blocks for each day of the week (from $fromDate on).
     */
    public function generateBlocks(StudyWeek $week, string $gear, StudyPreferences $prefs, ?CarbonInterface $fromDate = null): void
    {
        foreach ($this->plannedDays($week->week_start, $gear, $prefs) as $day) {
            if ($fromDate !== null && $day['date']->lt(Carbon::parse($fromDate)->startOfDay())) {
                continue;
            }

            foreach ($day['blocks'] as $block) {
                StudyBlock::create([
                    'user_id' => $week->user_id,
                    'study_week_id' => $week->id,
                    'block_date' => $day['date']->toDateString(),
                    'slot' => $block['slot'],
                    'lane' => $block['lane'],
                    'planned_minutes' => $block['minutes'],
                    'status' => 'planned',
                ]);
            }
        }
    }

    /**
     * Each gear's label, description and weekly minutes/hours for the user's
     * study profile and off days.
     *
     * @return array<int, array{gear: string, label: string, description: string, minutes: int, hours: int}>
     */
    public function gearOptions(StudyPreferences $prefs, ?CarbonInterface $weekStart = null): array
    {
        $weekStart ??= $this->weekStart(today(), $prefs);
        $profile = $prefs->studyProfile();

        return collect(StudyWeek::GEARS)->map(function (string $gear) use ($prefs, $weekStart, $profile) {
            $minutes = collect($this->plannedDays($weekStart, $gear, $prefs))
                ->sum(fn ($day) => array_sum(array_column($day['blocks'], 'minutes')));
            $text = config("study.gear_descriptions.{$profile}.{$gear}");

            return [
                'gear' => $gear,
                'label' => $text['label'] ?? ucfirst($gear),
                'description' => $text['description'] ?? '',
                'minutes' => (int) $minutes,
                'hours' => (int) round($minutes / 60),
            ];
        })->all();
    }

    /**
     * The template blocks for each of the seven days of a week, for the user's
     * profile: workdays (office or class days) vs off days, with the
     * first-off-day-only blocks on the week's first off day.
     *
     * @return array<int, array{date: Carbon, blocks: array}>
     */
    private function plannedDays(CarbonInterface $weekStart, string $gear, StudyPreferences $prefs): array
    {
        $template = config("study.gear_templates.{$prefs->studyProfile()}.{$gear}");
        $offDays = $prefs->offDays();
        $start = Carbon::parse($weekStart)->startOfDay();
        $days = [];
        $seenOffDay = false;

        for ($d = 0; $d < 7; $d++) {
            $date = $start->copy()->addDays($d);
            $isOff = in_array($date->dayOfWeek, $offDays, true);
            $blocks = $isOff ? $template['off'] : $template['workday'];

            if ($isOff && ! $seenOffDay) {
                $blocks = array_merge($template['first_off_day_only'] ?? [], $blocks);
                $seenOffDay = true;
            }

            $days[] = ['date' => $date, 'blocks' => $blocks];
        }

        return $days;
    }

    /** Replace today's and later still-planned blocks with another gear's template. */
    public function regenerate(StudyWeek $week, string $gear, StudyPreferences $prefs): void
    {
        DB::transaction(function () use ($week, $gear, $prefs) {
            $from = today()->max($week->week_start->copy()->startOfDay());

            $week->blocks()
                ->whereDate('block_date', '>=', $from->toDateString())
                ->where('status', 'planned')
                ->delete();

            $week->update(['gear' => $gear]);

            if ($from->lte($week->weekEnd())) {
                $this->generateBlocks($week, $gear, $prefs, $from);
            }
        });
    }

    /** @return Collection<int, StudyBlock> blocks ordered by date, then slot */
    public function orderedBlocks(StudyWeek $week): Collection
    {
        $order = array_flip(config('study.slot_order'));

        return $week->blocks()->with('topic:id,title', 'category:id,name,color')->get()
            ->sortBy(fn (StudyBlock $b) => sprintf('%s-%02d-%010d', $b->block_date->toDateString(), $order[$b->slot] ?? 99, $b->id))
            ->values();
    }

    /**
     * Counted blocks: not red, and dated before today or already marked.
     * A planned block dated before today counts as missed; partial counts half.
     */
    public function score(Collection $blocks, StudyPreferences $prefs, ?CarbonInterface $today = null): array
    {
        $today = Carbon::parse($today ?? today())->startOfDay();

        $counted = $blocks->filter(fn (StudyBlock $b) => $b->status !== 'red'
            && ($b->block_date->lt($today) || in_array($b->status, ['done', 'partial', 'missed'], true)));

        $done = $counted->where('status', 'done')->count();
        $partial = $counted->where('status', 'partial')->count();
        $total = $counted->count();
        $percent = $total > 0 ? (int) round(100 * ($done + 0.5 * $partial) / $total) : null;
        $planned = $blocks->where('status', '!=', 'red');

        return [
            'counted' => $total,
            'done' => $done,
            'partial' => $partial,
            'missed' => $total - $done - $partial,
            'red' => $blocks->where('status', 'red')->count(),
            'percent' => $percent,
            'on_track' => $percent !== null && $percent >= $prefs->successThresholdPercent(),
            'planned_total' => $planned->count(),
            'planned_minutes_total' => (int) $planned->sum('planned_minutes'),
        ];
    }

    public function stats(User $user, Carbon $weekStart, StudyPreferences $prefs): array
    {
        $start = $weekStart->copy()->startOfDay();
        $end = $start->copy()->addDays(7);

        $completed = StudyTask::where('user_id', $user->id)
            ->where('task_type', 'revision')
            ->where('status', 'completed')
            ->where('completed_at', '>=', $start)
            ->where('completed_at', '<', $end);

        return [
            'overdue_topics' => ReviewLoadService::dueTasksQuery($user->id, today())
                ->whereDate('scheduled_date', '<', today()->toDateString())
                ->distinct()
                ->count('topic_id'),
            'reviews_completed' => (clone $completed)->count(),
            'review_minutes' => (int) round(((clone $completed)->sum('review_seconds')) / 60),
            'new_topics' => Topic::where('user_id', $user->id)->regular()
                ->where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
            'weekly_new_topic_cap' => $prefs->weeklyNewTopicCap(),
        ];
    }

    /** @return array<int, array> the user's latest plans (up to today), newest first */
    public function history(User $user, int $weeks): array
    {
        $prefs = StudyPreferences::for($user);

        $plans = StudyWeek::where('user_id', $user->id)
            ->whereDate('week_start', '<=', today()->toDateString())
            ->orderByDesc('week_start')
            ->limit($weeks)
            ->with('blocks')
            ->get();

        return $plans->map(function (StudyWeek $week) use ($prefs) {
            $score = $this->score($week->blocks, $prefs);

            return [
                'id' => IdHasher::encode($week->id),
                'week_start' => $week->week_start->toDateString(),
                'gear' => $week->gear,
                'percent' => $score['percent'],
                'done' => $score['done'],
                'partial' => $score['partial'],
                'counted' => $score['counted'],
                'on_track' => $score['on_track'],
            ];
        })->all();
    }
}
