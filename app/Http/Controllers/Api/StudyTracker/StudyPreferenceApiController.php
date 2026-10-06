<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\UpdateStudyPreferencesRequest;
use App\Services\StudyTracker\StudyPreferences;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StudyPreferenceApiController extends Controller
{
    use CustomResponseTrait;

    /**
     * GET /api/study/preferences
     */
    public function show(Request $request): JsonResponse
    {
        return $this->jsonResponse(
            flag: true,
            message: 'Study preferences fetched successfully.',
            data: StudyPreferences::for($request->user())->all(),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * PUT /api/study/preferences
     */
    public function update(UpdateStudyPreferencesRequest $request): JsonResponse
    {
        $prefs = StudyPreferences::update($request->user(), $request->validated());

        return $this->jsonResponse(
            flag: true,
            message: 'Study preferences updated successfully.',
            data: $prefs->all(),
            responseCode: HttpResponse::HTTP_OK,
        );
    }
}
