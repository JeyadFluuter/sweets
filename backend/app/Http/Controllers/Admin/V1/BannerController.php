<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Banner\StoreBannerRequest;
use App\Http\Requests\Admin\V1\Banner\UpdateBannerRequest;
use App\Http\Resources\Api\V1\BannerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Banner;
use App\Services\Catalog\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function __construct(private readonly BannerService $bannerService)
    {
        $this->authorizeResource(Banner::class, 'banner');
    }

    public function index(Request $request): JsonResponse
    {
        $banners = $this->bannerService->list(
            $request->only(['is_active']),
            $request->integer('per_page', 15)
        );

        return ApiResponse::success(BannerResource::collection($banners)->response()->getData(true));
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['image']);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $banner = $this->bannerService->create($data);

        return ApiResponse::success(new BannerResource($banner), __('messages.banners.created'), 201);
    }

    public function show(Banner $banner): JsonResponse
    {
        return ApiResponse::success(new BannerResource($banner->load('media')));
    }

    public function update(UpdateBannerRequest $request, Banner $banner): JsonResponse
    {
        $data = $request->safe()->except(['image']);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $banner = $this->bannerService->update($banner, $data);

        return ApiResponse::success(new BannerResource($banner), __('messages.banners.updated'));
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $this->bannerService->delete($banner);

        return ApiResponse::success(null, __('messages.banners.deleted'));
    }
}
