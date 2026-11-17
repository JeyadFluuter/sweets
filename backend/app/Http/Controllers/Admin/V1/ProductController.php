<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Product\StoreProductRequest;
use App\Http\Requests\Admin\V1\Product\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use App\Services\Catalog\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService)
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list(
            $request->only(['search', 'category_id', 'is_available', 'tag_id', 'on_discount']),
            $request->integer('per_page', 15)
        );

        return ApiResponse::success(ProductResource::collection($products)->response()->getData(true));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['images']);
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $product = $this->productService->create($data);

        return ApiResponse::success(new ProductResource($product), __('messages.products.created'), 201);
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(new ProductResource($product->load(['category', 'tags', 'media'])));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->safe()->except(['images']);
        if ($request->hasFile('images')) {
            $data['images'] = $request->file('images');
        }

        $product = $this->productService->update($product, $data);

        return ApiResponse::success(new ProductResource($product), __('messages.products.updated'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return ApiResponse::success(null, __('messages.products.deleted'));
    }
}
