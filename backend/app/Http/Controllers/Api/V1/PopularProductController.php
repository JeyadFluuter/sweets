<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Services\Catalog\PopularProductsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PopularProductController extends Controller
{
    public function __construct(private readonly PopularProductsService $popularProductsService) {}

    public function index(Request $request): JsonResponse
    {
        $limit = min(100, max(1, $request->integer('limit', 50)));

        return ApiResponse::success(
            ProductResource::collection($this->popularProductsService->all($limit))
        );
    }
}
