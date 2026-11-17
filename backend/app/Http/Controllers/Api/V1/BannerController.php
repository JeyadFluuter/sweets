<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Responses\ApiResponse;
use App\Services\Catalog\BannerService;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    public function __construct(private readonly BannerService $bannerService) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success(BannerResource::collection($this->bannerService->listActive()));
    }
}
