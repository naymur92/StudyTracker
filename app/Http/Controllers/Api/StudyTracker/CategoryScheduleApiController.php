<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\UpsertCategoryScheduleRequest;
use App\Models\Category;
use App\Models\CategoryReviewSchedule;
use App\Models\Topic;
use App\Models\TopicRevisionTemplate;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CategoryScheduleApiController extends Controller
{
    use CustomResponseTrait;

    /**
     * GET /api/study/schedule-presets
     */
    public function presets(): JsonResponse
    {
        $presets = collect(config('study.schedule_presets'))
            ->map(fn (array $preset, string $key) => [
                'key' => $key,
                'name' => $preset['name'],
                'description' => $preset['description'],
                'offsets' => $preset['offsets'],
                'repeat_every_days' => $preset['repeat_every_days'],
            ])
            ->values();

        return $this->jsonResponse(
            flag: true,
            message: 'Schedule presets fetched successfully.',
            data: $presets,
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * GET /api/study/categories/{category}/schedule
     */
    public function show(Request $request, Category $category): JsonResponse
    {
        $userId = $request->user()->id;
        $this->authorise($category, $userId);

        $schedule = CategoryReviewSchedule::where('user_id', $userId)->where('category_id', $category->id)->first();

        return $this->jsonResponse(
            flag: true,
            message: 'Category schedule fetched successfully.',
            data: [
                'schedule' => $schedule ? $this->format($schedule) : null,
                'fallback' => $schedule ? null : $this->fallback($userId),
            ],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * PUT /api/study/categories/{category}/schedule
     */
    public function update(UpsertCategoryScheduleRequest $request, Category $category): JsonResponse
    {
        $userId = $request->user()->id;
        $this->authorise($category, $userId);

        $values = $request->schedule();

        $schedule = CategoryReviewSchedule::updateOrCreate(
            ['user_id' => $userId, 'category_id' => $category->id],
            $values,
        );

        $applied = 0;
        if ($request->boolean('apply_to_existing_topics')) {
            // Snapshot only: pending dates move at each topic's next scheduling event.
            $applied = Topic::where('user_id', $userId)
                ->where('category_id', $category->id)
                ->regular()
                ->where('status', '!=', 'archived')
                ->update([
                    'srs_offsets' => json_encode($values['offsets']),
                    'srs_repeat_every_days' => $values['repeat_every_days'],
                    'srs_repeat_until' => $values['repeat_until'],
                    'srs_schedule_source' => 'category',
                ]);
        }

        return $this->jsonResponse(
            flag: true,
            message: 'Category schedule saved.',
            data: $this->format($schedule->fresh()) + ['applied_to_topics' => $applied],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * DELETE /api/study/categories/{category}/schedule
     */
    public function destroy(Request $request, Category $category): JsonResponse
    {
        $userId = $request->user()->id;
        $this->authorise($category, $userId);

        CategoryReviewSchedule::where('user_id', $userId)->where('category_id', $category->id)->delete();

        return $this->jsonResponse(
            flag: true,
            message: 'Category schedule removed; new topics use your default schedule.',
            data: ['schedule' => null, 'fallback' => $this->fallback($userId)],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    private function authorise(Category $category, int $userId): void
    {
        if ($category->user_id !== null && $category->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function fallback(int $userId): string
    {
        return TopicRevisionTemplate::where('user_id', $userId)->where('is_active', true)->exists()
            ? 'user_default'
            : 'system_default';
    }

    private function format(CategoryReviewSchedule $schedule): array
    {
        return [
            'preset_key' => $schedule->preset_key,
            'offsets' => $schedule->offsets,
            'repeat_every_days' => $schedule->repeat_every_days,
            'repeat_until' => $schedule->repeat_until?->format('Y-m-d'),
        ];
    }
}
