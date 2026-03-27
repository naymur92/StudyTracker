<?php

namespace App\Services\StudyTracker;

use App\Models\PracticeLog;

class AddPracticeLogService
{
    public function execute(int $userId, array $data): PracticeLog
    {
        return PracticeLog::create([
            'user_id'          => $userId,
            'topic_id'         => $data['topic_id'],
            'task_id'          => $data['task_id'] ?? null,
            'practiced_on'     => $data['practiced_on'] ?? today()->toDateString(),
            'practice_type'    => $data['practice_type'] ?? 'other',
            'details'          => $data['details'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'outcome'          => $data['outcome'] ?? null,
        ]);
    }
}
