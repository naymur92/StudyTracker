<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\UpdateRevisionTemplatesRequest;
use App\Models\TopicRevisionTemplate;
use App\Services\IdHasher;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RevisionTemplateApiController extends Controller
{
    use CustomResponseTrait;

    /**
     * GET /api/study/revision-templates
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        $templates = TopicRevisionTemplate::getForUser($userId)
            ->map(fn(TopicRevisionTemplate $t) => [
                'id'          => IdHasher::encode($t->id),
                'name'        => $t->name,
                'sequence_no' => (int) $t->sequence_no,
                'day_offset'  => (int) $t->day_offset,
                'is_active'   => (bool) $t->is_active,
                'scope'       => $t->user_id ? 'user' : 'default',
            ])
            ->values();

        return $this->jsonResponse(
            flag: true,
            message: 'Revision templates fetched successfully.',
            data: $templates,
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * PUT /api/study/revision-templates
     */
    public function update(UpdateRevisionTemplatesRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $rows   = collect($request->validated('templates'))
            ->sortBy('sequence_no')
            ->values();

        DB::transaction(function () use ($userId, $rows) {
            TopicRevisionTemplate::where('user_id', $userId)->delete();

            foreach ($rows as $row) {
                TopicRevisionTemplate::create([
                    'user_id'     => $userId,
                    'name'        => $row['name'] ?? ('Revision ' . $row['sequence_no']),
                    'day_offset'  => $row['day_offset'],
                    'sequence_no' => $row['sequence_no'],
                    'is_active'   => $row['is_active'] ?? true,
                ]);
            }
        });

        return $this->jsonResponse(
            flag: true,
            message: 'Revision templates updated successfully.',
            data: TopicRevisionTemplate::getForUser($userId)
                ->map(fn(TopicRevisionTemplate $t) => [
                    'id'          => IdHasher::encode($t->id),
                    'name'        => $t->name,
                    'sequence_no' => (int) $t->sequence_no,
                    'day_offset'  => (int) $t->day_offset,
                    'is_active'   => (bool) $t->is_active,
                    'scope'       => $t->user_id ? 'user' : 'default',
                ])
                ->values(),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/revision-templates/reset
     */
    public function reset(): JsonResponse
    {
        $userId = auth()->id();

        TopicRevisionTemplate::where('user_id', $userId)->delete();

        return $this->jsonResponse(
            flag: true,
            message: 'Revision templates reset to system defaults.',
            data: TopicRevisionTemplate::getForUser($userId)
                ->map(fn(TopicRevisionTemplate $t) => [
                    'id'          => IdHasher::encode($t->id),
                    'name'        => $t->name,
                    'sequence_no' => (int) $t->sequence_no,
                    'day_offset'  => (int) $t->day_offset,
                    'is_active'   => (bool) $t->is_active,
                    'scope'       => $t->user_id ? 'user' : 'default',
                ])
                ->values(),
            responseCode: HttpResponse::HTTP_OK,
        );
    }
}
