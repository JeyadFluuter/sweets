<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\CollectionResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Services\Catalog\HomeService;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __construct(private readonly HomeService $homeService) {}

    public function __invoke(): JsonResponse
    {
        $payload = $this->homeService->payload();

        return ApiResponse::success([
            'banners' => BannerResource::collection($payload['banners']),
            'categories' => CategoryResource::collection($payload['categories']),
            'popular_products' => ProductResource::collection($payload['popular_products']),
            'offers' => ProductResource::collection($payload['offers']),
            'collections' => CollectionResource::collection($payload['collections']),
        ]);
    }
}
