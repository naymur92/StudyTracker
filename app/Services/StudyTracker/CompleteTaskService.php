<?php

namespace App\Services\StudyTracker;

use App\Models\StudyTask;
use Illuminate\Validation\ValidationException;

class CompleteTaskService
{
    public function execute(StudyTask $task, array $data = []): StudyTask
    {
        if ($task->status === 'completed') {
            throw ValidationException::withMessages([
                'task' => 'This task is already completed.',
            ]);
        }

        $task->update([
            'status'              => 'completed',
            'completed_at'        => now(),
            'locked_at'           => now(),
            'is_date_locked'      => true,
            'notes'               => $data['notes'] ?? $task->notes,
            'difficulty_feedback' => $data['difficulty_feedback'] ?? null,
        ]);

        return $task->fresh();
    }
}
