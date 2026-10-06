<?php

namespace App\Services\StudyTracker;

use App\Models\Topic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Mistake notebook: a wrong answer stored as a topic of kind "mistake",
 * reviewed on the mistakes schedule (+1, +3, +7) and finally merged into its
 * parent topic's recall questions.
 */
class MistakeService
{
    public const STATE_ACTIVE = 'active';

    public const STATE_READY = 'ready';

    public const STATE_CLOSED = 'closed';

    public function __construct(
        private ResolveScheduleService $scheduleResolver,
        private GenerateRevisionTasksService $revisionService,
    ) {}

    public function create(int $userId, array $data): Topic
    {
        return DB::transaction(function () use ($userId, $data) {
            $parent = ! empty($data['parent_topic_id']) ? Topic::find($data['parent_topic_id']) : null;
            $schedule = $this->scheduleResolver->forNewTopic($userId, null, Topic::KIND_MISTAKE);

            $topic = new Topic([
                'user_id' => $userId,
                'kind' => Topic::KIND_MISTAKE,
                'parent_topic_id' => $parent?->id,
                'category_id' => $data['category_id'] ?? $parent?->category_id,
                'lane' => $parent?->lane,
                'title' => $this->title($data['question']),
                'slug' => $this->uniqueSlug($userId, $data['question']),
                'status' => 'active',
                'first_study_date' => $data['logged_on'] ?? today()->toDateString(),
                'recall_questions' => [['question' => $data['question'], 'answer' => $data['correct_answer']]],
                'mistake_details' => [
                    'my_answer' => $data['my_answer'] ?? null,
                    'correct_answer' => $data['correct_answer'],
                    'cause' => $data['cause'],
                    'source' => $data['source'] ?? null,
                ],
            ]);
            $this->scheduleResolver->applySnapshot($topic, $schedule);
            $topic->save();

            // No learn task: the correction is the learning. Reviews start the next day.
            $this->revisionService->execute($userId, $topic, $schedule);

            return $topic;
        });
    }

    public function update(Topic $mistake, array $data): Topic
    {
        $details = $mistake->mistake_details ?? [];
        $question = $data['question'] ?? $this->question($mistake);

        foreach (['my_answer', 'correct_answer', 'cause', 'source'] as $key) {
            if (array_key_exists($key, $data)) {
                $details[$key] = $data[$key];
            }
        }

        $mistake->mistake_details = $details;
        $mistake->title = $this->title($question);
        $mistake->recall_questions = [['question' => $question, 'answer' => $details['correct_answer'] ?? null]];

        if (array_key_exists('parent_topic_id', $data)) {
            $mistake->parent_topic_id = $data['parent_topic_id'];
        }
        if (array_key_exists('category_id', $data)) {
            $mistake->category_id = $data['category_id'];
        }

        $mistake->save();

        return $mistake;
    }

    /**
     * Close a ready mistake, appending its question to the parent topic.
     *
     * @return array{mistake: Topic, appended: bool}
     */
    public function merge(Topic $mistake): array
    {
        return DB::transaction(function () use ($mistake) {
            $mistake = Topic::lockForUpdate()->findOrFail($mistake->id);

            if ($this->state($mistake) !== self::STATE_READY) {
                throw ValidationException::withMessages([
                    'mistake' => 'Only mistakes that have finished their reviews can be merged.',
                ]);
            }

            $appended = false;
            $parent = $mistake->parent_topic_id ? Topic::lockForUpdate()->find($mistake->parent_topic_id) : null;

            if ($parent) {
                $questions = $parent->recall_questions ?? [];
                $question = $this->question($mistake);
                $normalise = fn ($q) => mb_strtolower(trim((string) $q));
                $exists = collect($questions)->contains(fn ($q) => $normalise($q['question'] ?? '') === $normalise($question));

                if (! $exists) {
                    $max = (int) config('study.review.max_recall_questions', 10);
                    if (count($questions) >= $max) {
                        throw ValidationException::withMessages([
                            'mistake' => "The parent topic already has the maximum of {$max} recall questions. Remove one first.",
                        ]);
                    }

                    $questions[] = ['question' => $question, 'answer' => $mistake->mistake_details['correct_answer'] ?? null];
                    $parent->recall_questions = $questions;
                    $parent->save();
                    $appended = true;
                }
            }

            $mistake->merged_at = now();
            $mistake->status = 'completed';
            $mistake->save();

            return ['mistake' => $mistake, 'appended' => $appended];
        });
    }

    public function state(Topic $mistake): string
    {
        if ($mistake->merged_at !== null) {
            return self::STATE_CLOSED;
        }

        $hasPending = array_key_exists('next_review_date', $mistake->getAttributes())
            ? $mistake->next_review_date !== null
            : $mistake->studyTasks()->where('task_type', 'revision')->whereIn('status', ['pending', 'missed'])->exists();

        return $hasPending ? self::STATE_ACTIVE : self::STATE_READY;
    }

    public function question(Topic $mistake): string
    {
        return (string) ($mistake->recall_questions[0]['question'] ?? $mistake->title);
    }

    private function title(string $question): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $question)), 197);
    }

    private function uniqueSlug(int $userId, string $question): string
    {
        $base = 'mistake-'.(Str::slug(Str::limit($question, 60, '')) ?: 'entry');
        $slug = $base;
        $i = 1;

        while (Topic::withTrashed()->where('user_id', $userId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
