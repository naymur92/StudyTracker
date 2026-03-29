<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\IdHasher;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                'is_demo'           => (bool) $user->is_demo,
                'created_at'        => $user->created_at?->format('Y-m-d H:i:s'),
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

    /**
     * PATCH /api/user
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'name' => $request->name,
        ])->save();

        return $this->jsonResponse(
            flag: true,
            message: 'Profile updated successfully.',
            data: [
                'id'                => IdHasher::encode($user->id),
                'name'              => $user->name,
                'email'             => $user->email,
                'created_at'        => $user->created_at?->format('Y-m-d H:i:s'),
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'is_active'         => (bool) $user->is_active,
                'status'            => $user->email_verified_at ? 'verified' : 'unverified',
            ],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/user/change-password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->new_password),
        ])->save();

        return $this->jsonResponse(
            flag: true,
            message: 'Password changed successfully.',
            data: [],
            responseCode: HttpResponse::HTTP_OK,
        );
    }
}
