<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\IndexPracticeLogRequest;
use App\Http\Requests\StudyTracker\StorePracticeLogRequest;
use App\Http\Requests\StudyTracker\UpdatePracticeLogRequest;
use App\Http\Resources\PracticeLogResource;
use App\Models\PracticeLog;
use App\Models\Topic;
use App\Services\StudyTracker\AddPracticeLogService;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PracticeLogApiController extends Controller
{
    use CustomResponseTrait;

    public function __construct(
        private AddPracticeLogService $addService
    ) {}

    /**
     * GET /api/study/practice-logs
     */
    public function index(IndexPracticeLogRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $query  = PracticeLog::with('topic', 'task')
            ->where('user_id', $userId)
            ->orderByDesc('practiced_on')
            ->orderByDesc('id');

        if ($request->filled('topic_id')) {
            $query->where('topic_id', $request->topic_id);
        }

        if ($request->filled('practice_type')) {
            $query->where('practice_type', $request->practice_type);
        }

        if ($request->filled('date_from')) {
            $query->where('practiced_on', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('practiced_on', '<=', $request->date_to);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $logs    = $query->paginate($perPage)->withQueryString();

        return $this->jsonResponse(
            flag: true,
            message: 'Practice logs fetched successfully.',
            data: PracticeLogResource::collection($logs),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/practice-logs
     */
    public function store(StorePracticeLogRequest $request): JsonResponse
    {
        $this->ensureTopicBelongsToUser($request->topic_id, $request->user()->id);

        $log = $this->addService->execute($request->user()->id, $request->validated());
        $log->load('topic');

        return $this->jsonResponse(
            flag: true,
            message: 'Practice log added.',
            data: new PracticeLogResource($log),
            responseCode: HttpResponse::HTTP_CREATED,
        );
    }

    /**
     * PUT /api/study/practice-logs/{practiceLog}
     */
    public function update(UpdatePracticeLogRequest $request, PracticeLog $practiceLog): JsonResponse
    {
        $this->authorise($practiceLog, $request->user()->id);

        $practiceLog->update($request->validated());

        return $this->jsonResponse(
            flag: true,
            message: 'Practice log updated.',
            data: new PracticeLogResource($practiceLog->fresh('topic')),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * DELETE /api/study/practice-logs/{practiceLog}
     */
    public function destroy(Request $request, PracticeLog $practiceLog): JsonResponse
    {
        $this->authorise($practiceLog, $request->user()->id);

        $practiceLog->delete();

        return $this->jsonResponse(
            flag: true,
            message: 'Practice log deleted.',
            data: [],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    private function authorise(PracticeLog $log, int $userId): void
    {
        if ($log->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function ensureTopicBelongsToUser(int $topicId, int $userId): void
    {
        $topic = Topic::find($topicId);
        if (! $topic || $topic->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }
    }
}
