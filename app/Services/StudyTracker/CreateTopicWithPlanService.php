<?php

namespace App\Services\StudyTracker;

use App\Models\Category;
use App\Models\StudyTask;
use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTopicWithPlanService
{
    public function __construct(
        private GenerateRevisionTasksService $revisionService
    ) {}

    public function execute(int $userId, array $data): Topic
    {
        return DB::transaction(function () use ($userId, $data) {
            $slug = $this->makeUniqueSlug($userId, $data['title']);

            $topic = Topic::create([
                'user_id'          => $userId,
                'category_id'      => $data['category_id'] ?? null,
                'title'            => $data['title'],
                'slug'             => $slug,
                'description'      => $data['description'] ?? null,
                'source_link'      => $data['source_link'] ?? null,
                'difficulty'       => $data['difficulty'] ?? null,
                'status'           => 'active',
                'first_study_date' => $data['first_study_date'],
                'notes'            => $data['notes'] ?? null,
                'tags'             => $data['tags'] ?? null,
            ]);

            // Create the initial "Learn" task
            StudyTask::create([
                'user_id'        => $userId,
                'topic_id'       => $topic->id,
                'task_type'      => 'learn',
                'title'          => 'Learn: ' . $topic->title,
                'scheduled_date' => $data['first_study_date'],
                'status'         => 'pending',
            ]);

            // Auto-generate revision tasks based on template
            $this->revisionService->execute($userId, $topic);

            return $topic;
        });
    }

    private function makeUniqueSlug(int $userId, string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;

        while (Topic::withTrashed()->where('user_id', $userId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
