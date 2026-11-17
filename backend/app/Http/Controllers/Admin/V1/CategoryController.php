<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Category\StoreCategoryRequest;
use App\Http\Requests\Admin\V1\Category\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService)
    {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->list(
            $request->only(['search', 'is_active', 'parent_id']),
            $request->integer('per_page', 15)
        );

        return ApiResponse::success(CategoryResource::collection($categories)->response()->getData(true));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['image']);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $category = $this->categoryService->create($data);

        return ApiResponse::success(new CategoryResource($category), __('messages.categories.created'), 201);
    }

    public function show(Category $category): JsonResponse
    {
        return ApiResponse::success(new CategoryResource($category->load(['parent', 'children', 'media'])));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->safe()->except(['image']);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $category = $this->categoryService->update($category, $data);

        return ApiResponse::success(new CategoryResource($category), __('messages.categories.updated'));
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return ApiResponse::success(null, __('messages.categories.deleted'));
    }
}
