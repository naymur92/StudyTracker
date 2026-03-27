<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\CompleteTaskRequest;
use App\Http\Requests\StudyTracker\RescheduleTaskRequest;
use App\Http\Resources\StudyTaskResource;
use App\Models\StudyTask;
use App\Services\StudyTracker\BuildDailyAgendaService;
use App\Services\StudyTracker\CompleteTaskService;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StudyTaskApiController extends Controller
{
    use CustomResponseTrait;

    public function __construct(
        private BuildDailyAgendaService $agendaService,
        private CompleteTaskService $completeService,
    ) {}

    /**
     * GET /api/study/daily-tasks?date=YYYY-MM-DD
     */
    public function daily(Request $request): JsonResponse
    {
        $date   = $request->get('date', today()->toDateString());
        $agenda = $this->agendaService->execute($request->user()->id, $date);

        return $this->jsonResponse(
            flag: true,
            message: 'Daily tasks fetched successfully.',
            data: $agenda,
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/tasks/{task}/complete
     */
    public function complete(CompleteTaskRequest $request, StudyTask $task): JsonResponse
    {
        $this->authorise($task, $request->user()->id);

        try {
            $updated = $this->completeService->execute($task, $request->validated());
        } catch (ValidationException $e) {
            return $this->jsonResponse(
                message: $e->getMessage(),
                data: ['errors' => $e->errors()],
                responseCode: HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->jsonResponse(
            flag: true,
            message: 'Task marked as completed!',
            data: new StudyTaskResource($updated),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/tasks/{task}/skip
     */
    public function skip(Request $request, StudyTask $task): JsonResponse
    {
        $this->authorise($task, $request->user()->id);

        if ($task->is_date_locked) {
            return $this->jsonResponse(
                message: 'Completed tasks cannot be changed.',
                data: [],
                responseCode: HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $task->update(['status' => 'skipped']);

        return $this->jsonResponse(
            flag: true,
            message: 'Task skipped.',
            data: new StudyTaskResource($task->fresh()),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/tasks/{task}/reschedule
     */
    public function reschedule(RescheduleTaskRequest $request, StudyTask $task): JsonResponse
    {
        $this->authorise($task, $request->user()->id);

        if ($task->is_date_locked) {
            return $this->jsonResponse(
                message: 'Completed tasks cannot be rescheduled.',
                data: [],
                responseCode: HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $task->update(['scheduled_date' => $request->scheduled_date]);

        return $this->jsonResponse(
            flag: true,
            message: 'Task rescheduled to ' . $request->scheduled_date . '.',
            data: new StudyTaskResource($task->fresh()),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    private function authorise(StudyTask $task, int $userId): void
    {
        if ($task->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }
    }
}
