<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\IndexTopicRequest;
use App\Http\Requests\StudyTracker\StoreTopicRequest;
use App\Http\Requests\StudyTracker\UpdateTopicRequest;
use App\Http\Resources\PracticeLogResource;
use App\Http\Resources\StudyTaskResource;
use App\Http\Resources\TopicResource;
use App\Models\Category;
use App\Models\PracticeLog;
use App\Models\StudyTask;
use App\Models\Topic;
use App\Services\StudyTracker\CreateTopicWithPlanService;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TopicApiController extends Controller
{
    use CustomResponseTrait;

    public function __construct(
        private CreateTopicWithPlanService $createService
    ) {}

    /**
     * GET /api/study/topics
     */
    public function index(IndexTopicRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $query  = Topic::with('category')->where('user_id', $userId)->latest();

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $topics  = $query->paginate($perPage)->withQueryString();

        return $this->jsonResponse(
            flag: true,
            message: 'Topics fetched successfully.',
            data: TopicResource::collection($topics),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/topics
     */
    public function store(StoreTopicRequest $request): JsonResponse
    {
        $topic = $this->createService->execute($request->user()->id, $request->validated());

        $topic->load('category', 'studyTasks');

        return $this->jsonResponse(
            flag: true,
            message: 'Topic added successfully with revision plan.',
            data: new TopicResource($topic),
            responseCode: HttpResponse::HTTP_CREATED,
        );
    }

    /**
     * GET /api/study/topics/{topic}
     */
    public function show(Request $request, Topic $topic): JsonResponse
    {
        $this->authorise($topic, $request->user()->id);

        $topic->load('category');

        $studyTasks = StudyTask::where('topic_id', $topic->id)
            ->orderBy('task_type')
            ->orderBy('scheduled_date')
            ->get();

        $practiceLogs = PracticeLog::where('topic_id', $topic->id)
            ->orderByDesc('practiced_on')
            ->get();

        $taskStats = [
            'total'     => $studyTasks->count(),
            'completed' => $studyTasks->where('status', 'completed')->count(),
            'pending'   => $studyTasks->whereIn('status', ['pending', 'missed'])->count(),
        ];

        return $this->jsonResponse(
            flag: true,
            message: 'Topic details fetched successfully.',
            data: [
                'topic'         => new TopicResource($topic),
                'study_tasks'   => StudyTaskResource::collection($studyTasks),
                'practice_logs' => PracticeLogResource::collection($practiceLogs),
                'task_stats'    => $taskStats,
            ],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * PUT /api/study/topics/{topic}
     */
    public function update(UpdateTopicRequest $request, Topic $topic): JsonResponse
    {
        $this->authorise($topic, $request->user()->id);

        $data = $request->validated();
        unset($data['first_study_date']); // immutable after creation

        $topic->update($data);

        return $this->jsonResponse(
            flag: true,
            message: 'Topic updated successfully.',
            data: new TopicResource($topic->fresh('category')),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * DELETE /api/study/topics/{topic}
     */
    public function destroy(Request $request, Topic $topic): JsonResponse
    {
        $this->authorise($topic, $request->user()->id);

        $topic->delete();

        return $this->jsonResponse(
            flag: true,
            message: 'Topic archived.',
            data: [],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    private function authorise(Topic $topic, int $userId): void
    {
        if ($topic->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }
    }
}
