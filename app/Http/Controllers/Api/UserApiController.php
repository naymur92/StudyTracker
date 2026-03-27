<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IdHasher;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserApiController extends Controller
{
    use CustomResponseTrait;

    /**
     * GET /api/user
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->loadCount([
            'topics',
            'studyTasks',
            'practiceLogs',
        ]);

        return $this->jsonResponse(
            flag: true,
            message: 'User profile fetched successfully.',
            data: [
                'id'                => IdHasher::encode($user->id),
                'name'              => $user->name,
                'email'             => $user->email,
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'is_active'         => (bool) $user->is_active,
                'status'            => $user->email_verified_at ? 'verified' : 'unverified',
                'topics_count'      => (int) $user->topics_count,
                'tasks_count'       => (int) $user->study_tasks_count,
                'practice_logs_count' => (int) $user->practice_logs_count,
            ],
            responseCode: HttpResponse::HTTP_OK,
        );
    }
}
