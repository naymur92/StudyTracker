<?php

namespace App\Services\StudyTracker;

use App\Models\StudyTask;
use App\Services\IdHasher;
use Carbon\Carbon;

class BuildDailyAgendaService
{
    public function execute(int $userId, string $date): array
    {
        $targetDate = Carbon::parse($date);

        // Fetch all tasks for the user:
        // - Scheduled today, OR
        // - Overdue (scheduled before today and still pending/missed)
        $tasks = StudyTask::with('topic.category')
            ->where('user_id', $userId)
            ->whereHas('topic')
            ->where(function ($query) use ($date) {
                $query->where('scheduled_date', $date)
                    ->orWhere(function ($q) use ($date) {
                        $q->where('scheduled_date', '<', $date)
                            ->whereIn('status', ['pending', 'missed']);
                    });
            })
            ->orderBy('scheduled_date')
            ->orderBy('task_type')
            ->get();

        $groups = [
            'learn'      => [],
            'revision_1' => [],
            'revision_2' => [],
            'revision_3' => [],
            'revision_4' => [],
            'practice'   => [],
            'overdue'     => [],
        ];

        $total     = 0;
        $completed = 0;

        foreach ($tasks as $task) {
            $total++;
            if ($task->status === 'completed') {
                $completed++;
            }

            $isOverdue = $task->scheduled_date->lt($targetDate)
                && in_array($task->status, ['pending', 'missed']);

            if ($isOverdue) {
                $groups['overdue'][] = $this->formatTask($task);
                continue;
            }

            if ($task->task_type === 'learn') {
                $groups['learn'][] = $this->formatTask($task);
            } elseif ($task->task_type === 'revision') {
                $key = 'revision_' . $task->revision_no;
                $groups[$key][] = $this->formatTask($task);
            } elseif ($task->task_type === 'practice') {
                $groups['practice'][] = $this->formatTask($task);
            }
        }

        // Remove empty groups, then order: learn → revision_N (ascending) → practice → overdue
        $groups = array_filter($groups, fn($g) => count($g) > 0);
        $groups = $this->orderGroups($groups);

        return [
            'date'    => $date,
            'summary' => [
                'total'     => $total,
                'completed' => $completed,
                'pending'   => $total - $completed,
            ],
            'groups' => $groups,
        ];
    }

    private function formatTask(StudyTask $task): array
    {
        return [
            'id'                   => IdHasher::encode($task->id),
            'title'                => $task->title,
            'task_type'            => $task->task_type,
            'revision_no'          => $task->revision_no,
            'type_label'           => $task->taskTypeLabel(),
            'topic_id'             => IdHasher::encode($task->topic_id),
            'topic_title'        => $task->topic->title ?? '',
            'topic_category'     => $task->topic->category->name ?? null,
            'topic_category_color' => $task->topic->category->color ?? null,
            'scheduled_date'     => $task->scheduled_date->toDateString(),
            'status'             => $task->status,
            'status_badge'       => $task->statusBadgeClass(),
            'completed_at'       => $task->completed_at?->toDateTimeString(),
            'is_date_locked'     => $task->is_date_locked,
            'notes'              => $task->notes,
            'difficulty_feedback' => $task->difficulty_feedback,
            'recall_grade'       => $task->recall_grade,
            'review_seconds'     => $task->review_seconds,
            'review_kind'        => $task->review_kind,
            'can_reschedule'     => $task->canBeRescheduled(),
        ];
    }

    private function orderGroups(array $groups): array
    {
        $rank = function (string $key): array {
            if ($key === 'learn') {
                return [0, 0];
            }
            if (str_starts_with($key, 'revision_')) {
                return [1, (int) substr($key, 9)];
            }

            return [$key === 'practice' ? 2 : 3, 0];
        };

        uksort($groups, fn ($a, $b) => $rank($a) <=> $rank($b));

        return $groups;
    }
}
