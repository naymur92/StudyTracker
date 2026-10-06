<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\StoreMistakeRequest;
use App\Http\Resources\MistakeResource;
use App\Models\Topic;
use App\Services\IdHasher;
use App\Services\StudyTracker\MistakeService;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MistakeApiController extends Controller
{
    use CustomResponseTrait;

    public function __construct(private MistakeService $service) {}

    /**
     * GET /api/study/mistakes
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'state' => ['nullable', Rule::in(['open', 'active', 'ready', 'closed', 'all'])],
            'cause' => ['nullable', Rule::in(Topic::MISTAKE_CAUSES)],
            'parent_topic_id' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $pending = fn ($q) => $q->where('task_type', 'revision')->whereIn('status', ['pending', 'missed']);

        $query = Topic::mistakeEntries()
            ->where('user_id', $request->user()->id)
            ->with('category', 'parentTopic')
            ->withNextReviewDate()
            ->latest();

        match ($request->input('state', 'open')) {
            'open' => $query->whereNull('merged_at'),
            'active' => $query->whereNull('merged_at')->whereHas('studyTasks', $pending),
            'ready' => $query->whereNull('merged_at')->whereDoesntHave('studyTasks', $pending),
            'closed' => $query->whereNotNull('merged_at'),
            default => null,
        };

        if ($request->filled('cause')) {
            $query->where('mistake_details->cause', $request->input('cause'));
        }

        if ($request->filled('parent_topic_id')) {
            $query->where('parent_topic_id', IdHasher::decode((string) $request->input('parent_topic_id')) ?? 0);
        }

        $mistakes = $query->paginate(min((int) $request->get('per_page', 15), 100))->withQueryString();

        return $this->jsonResponse(
            flag: true,
            message: 'Mistakes fetched successfully.',
            data: MistakeResource::collection($mistakes),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/mistakes
     */
    public function store(StoreMistakeRequest $request): JsonResponse
    {
        $mistake = $this->service->create($request->user()->id, $request->validated());

        return $this->jsonResponse(
            flag: true,
            message: 'Mistake logged. First review tomorrow.',
            data: new MistakeResource($this->fresh($mistake)),
            responseCode: HttpResponse::HTTP_CREATED,
        );
    }

    /**
     * PATCH /api/study/mistakes/{mistake}
     */
    public function update(StoreMistakeRequest $request, Topic $mistake): JsonResponse
    {
        $this->authorise($mistake, $request->user()->id);

        $this->service->update($mistake, $request->validated());

        return $this->jsonResponse(
            flag: true,
            message: 'Mistake updated.',
            data: new MistakeResource($this->fresh($mistake)),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * DELETE /api/study/mistakes/{mistake}
     */
    public function destroy(Request $request, Topic $mistake): JsonResponse
    {
        $this->authorise($mistake, $request->user()->id);

        $mistake->delete();

        return $this->jsonResponse(
            flag: true,
            message: 'Mistake deleted.',
            data: [],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/mistakes/{mistake}/merge
     */
    public function merge(Request $request, Topic $mistake): JsonResponse
    {
        $this->authorise($mistake, $request->user()->id);

        try {
            $result = $this->service->merge($mistake);
        } catch (ValidationException $e) {
            return $this->jsonResponse(
                message: collect($e->errors())->flatten()->first(),
                data: ['errors' => $e->errors()],
                responseCode: HttpResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->jsonResponse(
            flag: true,
            message: $result['appended']
                ? 'Mistake merged into its parent topic.'
                : 'Mistake closed.',
            data: array_merge(
                (new MistakeResource($this->fresh($result['mistake'])))->resolve($request),
                ['appended_to_parent' => $result['appended']],
            ),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    private function authorise(Topic $mistake, int $userId): void
    {
        if (! $mistake->isMistake()) {
            abort(404, 'Mistake not found.');
        }

        if ($mistake->user_id !== $userId) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function fresh(Topic $mistake): Topic
    {
        return Topic::with('category', 'parentTopic')->withNextReviewDate()->findOrFail($mistake->id);
    }
}
