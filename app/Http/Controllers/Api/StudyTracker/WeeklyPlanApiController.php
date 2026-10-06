<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\StoreStudyWeekRequest;
use App\Http\Requests\StudyTracker\StudyBlockRequest;
use App\Http\Requests\StudyTracker\UpdateStudyWeekRequest;
use App\Http\Resources\StudyBlockResource;
use App\Http\Resources\StudyWeekResource;
use App\Models\StudyBlock;
use App\Models\StudyWeek;
use App\Models\User;
use App\Services\StudyTracker\BlockTimerService;
use App\Services\StudyTracker\StudyPreferences;
use App\Services\StudyTracker\WeeklyPlanService;
use App\Traits\CustomResponseTrait;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WeeklyPlanApiController extends Controller
{
    use CustomResponseTrait;

    public function __construct(private WeeklyPlanService $service, private BlockTimerService $timers) {}

    /**
     * GET /api/study/weekly-plan?date=YYYY-MM-DD
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : today();

        return $this->ok('Weekly plan fetched successfully.', $this->payload($request->user(), $date));
    }

    /**
     * GET /api/study/weekly-plan/history?weeks=8
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate(['weeks' => ['nullable', 'integer', 'min:1', 'max:26']]);
        $this->timers->settle($request->user());

        return $this->ok('Weekly history fetched successfully.', $this->service->history($request->user(), (int) $request->input('weeks', 8)));
    }

    /**
     * POST /api/study/weekly-plan
     */
    public function store(StoreStudyWeekRequest $request): JsonResponse
    {
        $week = $this->service->create($request->user(), $request->validated() + ['generate_blocks' => $request->boolean('generate_blocks', true)]);

        return $this->ok('Weekly plan created.', $this->payload($request->user(), $week->week_start), HttpResponse::HTTP_CREATED);
    }

    /**
     * PATCH /api/study/weekly-plan/{week}
     */
    public function update(UpdateStudyWeekRequest $request, StudyWeek $week): JsonResponse
    {
        $this->authorise($week, $request->user()->id);
        $week->update($request->validated());

        return $this->ok('Weekly plan updated.', $this->payload($request->user(), $week->week_start));
    }

    /**
     * DELETE /api/study/weekly-plan/{week}
     */
    public function destroy(Request $request, StudyWeek $week): JsonResponse
    {
        $this->authorise($week, $request->user()->id);
        $week->delete();

        return $this->ok('Weekly plan deleted.', []);
    }

    /**
     * POST /api/study/weekly-plan/{week}/regenerate
     */
    public function regenerate(Request $request, StudyWeek $week): JsonResponse
    {
        $this->authorise($week, $request->user()->id);
        $request->validate(['gear' => ['required', Rule::in(StudyWeek::GEARS)]]);

        $this->service->regenerate($week, $request->input('gear'), StudyPreferences::for($request->user()));

        return $this->ok('Remaining blocks regenerated for the '.$request->input('gear').' gear.', $this->payload($request->user(), $week->week_start));
    }

    /**
     * POST /api/study/weekly-plan/{week}/blocks
     */
    public function storeBlock(StudyBlockRequest $request, StudyWeek $week): JsonResponse
    {
        $this->authorise($week, $request->user()->id);

        $block = $week->blocks()->create($request->validated() + [
            'user_id' => $week->user_id,
            'status' => $request->input('status', 'planned'),
        ]);

        return $this->ok('Block added.', new StudyBlockResource($block->fresh('topic')), HttpResponse::HTTP_CREATED);
    }

    /**
     * PATCH /api/study/blocks/{block}
     */
    public function updateBlock(StudyBlockRequest $request, StudyBlock $block): JsonResponse
    {
        $this->authorise($block, $request->user()->id);
        $block->update($request->validated());

        return $this->ok('Block updated.', new StudyBlockResource($block->fresh('topic')));
    }

    /**
     * DELETE /api/study/blocks/{block}
     */
    public function destroyBlock(Request $request, StudyBlock $block): JsonResponse
    {
        $this->authorise($block, $request->user()->id);

        $this->timers->settle($request->user());
        if ($block->activeSession()->exists()) {
            throw ValidationException::withMessages(['block' => 'Stop the block\'s timer before deleting it.']);
        }

        $block->delete();

        return $this->ok('Block deleted.', []);
    }

    private function payload(User $user, CarbonInterface $date): array
    {
        // A timer that ran out while no page was open completes its block first.
        $this->timers->settle($user);

        $prefs = StudyPreferences::for($user);
        $week = $this->service->findWeek($user->id, $date);
        $weekStart = $week?->week_start->copy() ?? $this->service->weekStart($date, $prefs);
        $blocks = $week ? $this->service->orderedBlocks($week) : collect();

        return [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekStart->copy()->addDays(6)->toDateString(),
            'plan' => $week ? (new StudyWeekResource($week))->resolve() : null,
            'blocks' => StudyBlockResource::collection($blocks)->resolve(),
            'score' => $week ? $this->service->score($blocks, $prefs) : null,
            'stats' => $this->service->stats($user, $weekStart, $prefs),
            'success_threshold_percent' => $prefs->successThresholdPercent(),
            'study_profile' => $prefs->studyProfile(),
            'gear_options' => $this->service->gearOptions($prefs, $weekStart),
        ];
    }

    private function authorise(StudyWeek|StudyBlock $model, int $userId): void
    {
        if ($model->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function ok(string $message, mixed $data, int $code = HttpResponse::HTTP_OK): JsonResponse
    {
        return $this->jsonResponse(flag: true, message: $message, data: $data, responseCode: $code);
    }
}
