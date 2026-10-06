<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\StoreDataDeletionRequest;
use App\Http\Resources\DataDeletionRequestResource;
use App\Models\DataDeletionRequest;
use App\Services\StudyTracker\DataDeletion\CreateDataDeletionRequestService;
use App\Services\StudyTracker\DataDeletion\DataDeletionSummaryService;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DataDeletionApiController extends Controller
{
    use CustomResponseTrait;

    /**
     * GET /api/study/data-deletion/summary
     */
    public function summary(Request $request, DataDeletionSummaryService $summary): JsonResponse
    {
        return $this->jsonResponse(
            flag: true,
            message: 'Data summary fetched successfully.',
            data: ['categories' => $summary->forUser($request->user())],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * GET /api/study/data-deletion/requests
     */
    public function index(Request $request): JsonResponse
    {
        $requests = DataDeletionRequest::where('user_id', $request->user()->id)
            ->latest('id')
            ->get();

        return $this->jsonResponse(
            flag: true,
            message: 'Data deletion requests fetched successfully.',
            data: DataDeletionRequestResource::collection($requests),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/data-deletion/requests
     */
    public function store(StoreDataDeletionRequest $request, CreateDataDeletionRequestService $service): JsonResponse
    {
        $deletionRequest = $service->create(
            $request->user(),
            $request->validated('categories'),
            $request->validated('reason'),
        );

        return $this->jsonResponse(
            flag: true,
            message: 'Data deletion request submitted. An admin will review it.',
            data: new DataDeletionRequestResource($deletionRequest),
            responseCode: HttpResponse::HTTP_CREATED,
        );
    }

    /**
     * GET /api/study/data-deletion/requests/{dataDeletionRequest}
     */
    public function show(Request $request, DataDeletionRequest $dataDeletionRequest): JsonResponse
    {
        // 404 rather than 403: do not reveal that someone else's request exists.
        if ($dataDeletionRequest->user_id !== $request->user()->id) {
            abort(404, 'Data deletion request not found.');
        }

        return $this->jsonResponse(
            flag: true,
            message: 'Data deletion request fetched successfully.',
            data: new DataDeletionRequestResource($dataDeletionRequest),
            responseCode: HttpResponse::HTTP_OK,
        );
    }
}
