<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Exceptions\ActiveTimerConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\StartBlockTimerRequest;
use App\Http\Resources\BlockTimerResource;
use App\Http\Resources\StudyBlockResource;
use App\Models\StudyBlock;
use App\Models\StudyBlockSession;
use App\Models\User;
use App\Services\IdHasher;
use App\Services\StudyTracker\BlockTimerService;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Block timers. Every action returns {timer, recent, server_time}, plus the
 * acted-on `block`.
 */
class BlockTimerApiController extends Controller
{
    use CustomResponseTrait;

    public function __construct(private BlockTimerService $timers) {}

    /**
     * GET /api/study/timer
     */
    public function show(Request $request): JsonResponse
    {
        $this->timers->settle($request->user());

        return $this->ok('Timer fetched successfully.', $this->payload($request->user()));
    }

    /**
     * POST /api/study/blocks/{block}/timer/start
     */
    public function start(StartBlockTimerRequest $request, StudyBlock $block): JsonResponse
    {
        $this->authorise($block, $request->user());

        try {
            $this->timers->start($request->user(), $block, $request->validated());
        } catch (ActiveTimerConflictException $e) {
            return $this->jsonResponse(
                flag: false,
                message: $e->getMessage(),
                data: $this->payload($request->user()),
                responseCode: HttpResponse::HTTP_CONFLICT,
            );
        }

        return $this->ok('Timer started.', $this->payload($request->user(), $block));
    }

    /**
     * POST /api/study/blocks/{block}/timer/pause
     */
    public function pause(Request $request, StudyBlock $block): JsonResponse
    {
        $this->authorise($block, $request->user());
        $this->timers->pause($request->user(), $block);

        return $this->ok('Timer paused.', $this->payload($request->user(), $block));
    }

    /**
     * POST /api/study/blocks/{block}/timer/resume
     */
    public function resume(Request $request, StudyBlock $block): JsonResponse
    {
        $this->authorise($block, $request->user());
        $this->timers->resume($request->user(), $block);

        return $this->ok('Timer resumed.', $this->payload($request->user(), $block));
    }

    /**
     * POST /api/study/blocks/{block}/timer/stop
     */
    public function stop(Request $request, StudyBlock $block): JsonResponse
    {
        $this->authorise($block, $request->user());
        $outcome = $this->timers->stop($request->user(), $block);

        $message = match ($outcome) {
            'done' => 'Block finished.',
            'partial' => 'Timer stopped; the block is marked partial.',
            default => 'The run was shorter than a minute and was discarded.',
        };

        return $this->ok($message, $this->payload($request->user(), $block) + ['outcome' => $outcome]);
    }

    /**
     * DELETE /api/study/blocks/{block}/timer
     */
    public function discard(Request $request, StudyBlock $block): JsonResponse
    {
        $this->authorise($block, $request->user());
        $this->timers->discard($request->user(), $block);

        return $this->ok('Timer run discarded.', $this->payload($request->user(), $block));
    }

    private function payload(User $user, ?StudyBlock $block = null): array
    {
        $active = $this->timers->activeSession($user);
        $recent = $this->timers->recentSession($user);

        $payload = [
            'timer' => $active ? (new BlockTimerResource($active))->resolve() : null,
            'recent' => $recent ? $this->recent($recent) : null,
            'server_time' => now()->toIso8601String(),
        ];

        if ($block) {
            $payload['block'] = (new StudyBlockResource($block->fresh(['topic', 'activeSession'])))->resolve();
        }

        return $payload;
    }

    private function recent(StudyBlockSession $session): array
    {
        return [
            'session_id' => IdHasher::encode($session->id),
            'end_reason' => $session->end_reason,
            'ended_at' => $session->ended_at->toIso8601String(),
            'minutes' => (int) round($session->used_seconds / 60),
            'block' => (new StudyBlockResource($session->block))->resolve(),
        ];
    }

    private function authorise(StudyBlock $block, User $user): void
    {
        if ($block->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function ok(string $message, mixed $data): JsonResponse
    {
        return $this->jsonResponse(flag: true, message: $message, data: $data, responseCode: HttpResponse::HTTP_OK);
    }
}
