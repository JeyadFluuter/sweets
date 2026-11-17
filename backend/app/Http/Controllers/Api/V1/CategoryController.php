<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use App\Services\Catalog\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->with([
                'media',
                'children' => fn ($q) => $q->active()->orderBy('sort_order')->with('media'),
            ])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(CategoryResource::collection($categories));
    }

    public function show(Category $category): JsonResponse
    {
        abort_unless($category->is_active, 404);

        $category->load([
            'media',
            'children' => fn ($q) => $q->active()
                ->orderBy('sort_order')
                ->with([
                    'media',
                    'products' => fn ($pq) => $pq->available()
                        ->with(['category', 'tags', 'media'])
                        ->orderBy('sort_order'),
                ]),
            'products' => fn ($q) => $q->available()
                ->with(['category', 'tags', 'media'])
                ->orderBy('sort_order'),
        ]);

        return ApiResponse::success(new CategoryResource($category));
    }

    public function products(Request $request, Category $category): JsonResponse
    {
        abort_unless($category->is_active, 404);

        $products = $this->productService->list([
            'category_id' => $category->id,
            'is_available' => true,
            'search' => $request->string('search')->toString() ?: null,
            'tag_id' => $request->integer('tag_id') ?: null,
        ], $request->integer('per_page', 15));

        return ApiResponse::success([
            'category' => new CategoryResource($category->load('media')),
            'products' => ProductResource::collection($products)->response()->getData(true),
        ]);
    }
}
