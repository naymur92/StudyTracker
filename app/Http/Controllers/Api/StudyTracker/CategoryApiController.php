<?php

namespace App\Http\Controllers\Api\StudyTracker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyTracker\StoreCategoryRequest;
use App\Http\Requests\StudyTracker\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\CustomResponseTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CategoryApiController extends Controller
{
    use CustomResponseTrait;

    /**
     * GET /api/study/categories
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        $categories = Category::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhereNull('user_id');
        })
            ->withCount('topics')
            ->orderBy('name')
            ->get();

        return $this->jsonResponse(
            flag: true,
            message: 'Categories fetched successfully.',
            data: CategoryResource::collection($categories),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * POST /api/study/categories
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create([
            'user_id' => $request->user()->id,
            'name'    => $request->name,
            'color'   => $request->color,
            'icon'    => $request->icon,
        ]);

        return $this->jsonResponse(
            flag: true,
            message: 'Category created.',
            data: new CategoryResource($category),
            responseCode: HttpResponse::HTTP_CREATED,
        );
    }

    /**
     * PUT /api/study/categories/{category}
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorise($category, $request->user()->id);

        $category->update($request->validated());

        return $this->jsonResponse(
            flag: true,
            message: 'Category updated.',
            data: new CategoryResource($category->fresh()),
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    /**
     * DELETE /api/study/categories/{category}
     */
    public function destroy(\Illuminate\Http\Request $request, Category $category): JsonResponse
    {
        $this->authorise($category, $request->user()->id);

        $category->delete();

        return $this->jsonResponse(
            flag: true,
            message: 'Category deleted.',
            data: [],
            responseCode: HttpResponse::HTTP_OK,
        );
    }

    private function authorise(Category $category, int $userId): void
    {
        if ($category->user_id !== $userId) {
            abort(403, 'You cannot modify system categories.');
        }
    }
}
