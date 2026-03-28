<?php

namespace App\Services\StudyTracker;

use App\Models\PracticeLog;
use App\Models\StudyTask;
use Carbon\Carbon;

class BuildStudyReportService
{
    /**
     * @return array<int, mixed>
     */
    private function iterableToArray(mixed $value): array
    {
        if ($value instanceof \Traversable) {
            return iterator_to_array($value, false);
        }

        if (is_array($value)) {
            return $value;
        }

        if ($value === null) {
            return [];
        }

        return [$value];
    }

    private function getNestedValue(mixed $source, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $source;

        foreach ($segments as $segment) {
            if (is_array($current)) {
                if (! array_key_exists($segment, $current)) {
                    return null;
                }

                $current = $current[$segment];
                continue;
            }

            if (is_object($current)) {
                if (! isset($current->{$segment}) && ! property_exists($current, $segment)) {
                    return null;
                }

                $current = $current->{$segment};
                continue;
            }

            return null;
        }

        return $current;
    }

    private function formatDateValue(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    private function formatDateTimeValue(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    /**
     * @param array<int, string> $months
     * @return array<string, mixed>
     */
    public function buildReportData(int $userId, array $months): array
    {
        $months = collect($months)
            ->filter(fn($month) => is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $firstMonthStart = now()->startOfMonth();
        $lastMonthEnd = now()->endOfMonth();

        if (! empty($months)) {
            $firstMonthStart = Carbon::createFromFormat('Y-m', $months[0])->startOfMonth();
            $lastMonthEnd = Carbon::createFromFormat('Y-m', $months[count($months) - 1])->endOfMonth();
        }

        $monthLookup = array_flip($months);

        $tasks = StudyTask::query()
            ->with('topic:id,title')
            ->where('user_id', $userId)
            ->whereBetween('scheduled_date', [$firstMonthStart->toDateString(), $lastMonthEnd->toDateString()])
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->get()
            ->filter(fn(StudyTask $task) => isset($monthLookup[$task->scheduled_date->format('Y-m')]))
            ->values();

        $practiceLogs = PracticeLog::query()
            ->with('topic:id,title')
            ->where('user_id', $userId)
            ->whereBetween('practiced_on', [$firstMonthStart->toDateString(), $lastMonthEnd->toDateString()])
            ->orderBy('practiced_on')
            ->orderBy('id')
            ->get()
            ->filter(fn(PracticeLog $log) => isset($monthLookup[$log->practiced_on->format('Y-m')]))
            ->values();

        $summary = [];
        foreach ($months as $month) {
            $summary[$month] = [
                'tasks_total' => 0,
                'tasks_completed' => 0,
                'tasks_pending' => 0,
                'tasks_skipped' => 0,
                'tasks_missed' => 0,
                'practice_sessions' => 0,
                'practice_minutes' => 0,
            ];
        }

        foreach ($tasks as $task) {
            $month = $task->scheduled_date->format('Y-m');
            if (! isset($summary[$month])) {
                continue;
            }

            $summary[$month]['tasks_total']++;

            if ($task->status === 'completed') {
                $summary[$month]['tasks_completed']++;
            }

            if ($task->status === 'pending') {
                $summary[$month]['tasks_pending']++;
            }

            if ($task->status === 'skipped') {
                $summary[$month]['tasks_skipped']++;
            }

            if ($task->status === 'missed') {
                $summary[$month]['tasks_missed']++;
            }
        }

        foreach ($practiceLogs as $log) {
            $month = $log->practiced_on->format('Y-m');
            if (! isset($summary[$month])) {
                continue;
            }

            $summary[$month]['practice_sessions']++;
            $summary[$month]['practice_minutes'] += (int) ($log->duration_minutes ?? 0);
        }

        return [
            'generated_at' => now()->toDateTimeString(),
            'months' => $months,
            'summary' => $summary,
            'tasks' => $tasks,
            'practice_logs' => $practiceLogs,
        ];
    }

    /**
     * @param array<string, mixed> $reportData
     */
    public function toCsv(array $reportData): string
    {
        $stream = fopen('php://temp', 'w+');

        fputcsv($stream, ['StudyTracker Report']);
        fputcsv($stream, ['Generated At', (string) ($reportData['generated_at'] ?? '')]);
        fputcsv($stream, ['Months', implode(', ', (array) ($reportData['months'] ?? []))]);
        fputcsv($stream, []);

        fputcsv($stream, ['Monthly Summary']);
        fputcsv($stream, [
            'Month',
            'Tasks Total',
            'Tasks Completed',
            'Tasks Pending',
            'Tasks Skipped',
            'Tasks Missed',
            'Practice Sessions',
            'Practice Minutes',
        ]);

        foreach ((array) ($reportData['summary'] ?? []) as $month => $row) {
            fputcsv($stream, [
                $month,
                (int) ($row['tasks_total'] ?? 0),
                (int) ($row['tasks_completed'] ?? 0),
                (int) ($row['tasks_pending'] ?? 0),
                (int) ($row['tasks_skipped'] ?? 0),
                (int) ($row['tasks_missed'] ?? 0),
                (int) ($row['practice_sessions'] ?? 0),
                (int) ($row['practice_minutes'] ?? 0),
            ]);
        }

        fputcsv($stream, []);
        fputcsv($stream, ['Task Details']);
        fputcsv($stream, [
            'Date',
            'Topic',
            'Task Type',
            'Revision No',
            'Title',
            'Status',
            'Completed At',
            'Notes',
        ]);

        foreach ($this->iterableToArray($reportData['tasks'] ?? []) as $task) {
            fputcsv($stream, [
                $this->formatDateValue($this->getNestedValue($task, 'scheduled_date')),
                $this->getNestedValue($task, 'topic.title'),
                $this->getNestedValue($task, 'task_type'),
                $this->getNestedValue($task, 'revision_no'),
                $this->getNestedValue($task, 'title'),
                $this->getNestedValue($task, 'status'),
                $this->formatDateTimeValue($this->getNestedValue($task, 'completed_at')),
                $this->getNestedValue($task, 'notes'),
            ]);
        }

        fputcsv($stream, []);
        fputcsv($stream, ['Practice Log Details']);
        fputcsv($stream, [
            'Date',
            'Topic',
            'Practice Type',
            'Duration Minutes',
            'Outcome',
            'Details',
        ]);

        foreach ($this->iterableToArray($reportData['practice_logs'] ?? []) as $log) {
            fputcsv($stream, [
                $this->formatDateValue($this->getNestedValue($log, 'practiced_on')),
                $this->getNestedValue($log, 'topic.title'),
                $this->getNestedValue($log, 'practice_type'),
                $this->getNestedValue($log, 'duration_minutes'),
                $this->getNestedValue($log, 'outcome'),
                $this->getNestedValue($log, 'details'),
            ]);
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
