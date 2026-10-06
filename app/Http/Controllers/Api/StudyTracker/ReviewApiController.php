<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Services\StudyTracker\BuildReviewQueueService;
use App\Services\StudyTracker\ReviewLoadService;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReviewApiController extends Controller
{
    use CustomResponseTrait;

    public function __construct(
        private ReviewLoadService $loadService,
        private BuildReviewQueueService $queueService,
    ) {}

    /**
     * GET /api/study/review-queue?date=YYYY-MM-DD
     */
    public function queue(Request $request): JsonResponse
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return $this->jsonResponse(
            flag: true,
            message: 'Review queue fetched successfully.',
            data: $this->queueService->build($request->user(), $this->date($request)),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * GET /api/study/review-load?date=YYYY-MM-DD
     */
    public function load(Request $request): JsonResponse
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return $this->jsonResponse(
            flag: true,
            message: 'Review load fetched successfully.',
            data: $this->loadService->forDate($request->user(), $this->date($request)),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    private function date(Request $request): Carbon
    {
        return $request->filled('date') ? Carbon::parse($request->input('date')) : today();
    }
}
