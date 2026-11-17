<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Coupon\StoreCouponRequest;
use App\Http\Requests\Admin\V1\Coupon\UpdateCouponRequest;
use App\Http\Resources\Api\V1\CouponResource;
use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use App\Services\Coupons\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(private readonly CouponService $couponService)
    {
        $this->authorizeResource(Coupon::class, 'coupon');
    }

    public function index(Request $request): JsonResponse
    {
        $coupons = $this->couponService->list(
            $request->only(['search', 'is_active']),
            $request->integer('per_page', 15)
        );

        return ApiResponse::success(CouponResource::collection($coupons)->response()->getData(true));
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = $this->couponService->create($request->validated());

        return ApiResponse::success(new CouponResource($coupon), __('messages.coupons.created'), 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        return ApiResponse::success(new CouponResource($coupon->load(['categories', 'products'])));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $coupon = $this->couponService->update($coupon, $request->validated());

        return ApiResponse::success(new CouponResource($coupon), __('messages.coupons.updated'));
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->couponService->delete($coupon);

        return ApiResponse::success(null, __('messages.coupons.deleted'));
    }
}
