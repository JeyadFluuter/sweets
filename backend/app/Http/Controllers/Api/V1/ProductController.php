<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function show(Product $product): JsonResponse
    {
        abort_unless($product->is_available, 404);

        $product->load(['category', 'tags', 'media']);

        return ApiResponse::success(new ProductResource($product));
    }
}
