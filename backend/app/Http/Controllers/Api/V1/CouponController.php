<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Coupon\ApplyCouponRequest;
use App\Http\Resources\Api\V1\CouponResource;
use App\Http\Responses\ApiResponse;
use App\Services\Coupons\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(private readonly CouponService $couponService) {}

    public function apply(ApplyCouponRequest $request): JsonResponse
    {
        $customer = $request->user();
        $result = $this->couponService->validate(
            $request->validated('code'),
            [
                'items' => $request->validated('items') ?? [],
                'subtotal' => $request->validated('subtotal'),
            ],
            $customer instanceof \App\Models\Customer ? $customer : null
        );

        if (! $result['valid']) {
            return ApiResponse::error($result['message'], null, 422);
        }

        return ApiResponse::success([
            'discount' => $result['discount'],
            'coupon' => new CouponResource($result['coupon']),
        ], $result['message']);
    }
}
