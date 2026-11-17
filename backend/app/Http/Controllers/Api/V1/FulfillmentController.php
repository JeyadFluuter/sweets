<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Fulfillment\AvailableDatesRequest;
use App\Http\Requests\Api\V1\Fulfillment\SlotsRequest;
use App\Http\Responses\ApiResponse;
use App\Services\Cart\CartService;
use App\Services\Fulfillment\FulfillmentService;
use Illuminate\Http\JsonResponse;

class FulfillmentController extends Controller
{
    public function __construct(
        private readonly FulfillmentService $fulfillmentService,
        private readonly CartService $cartService,
    ) {}

    public function availableDates(AvailableDatesRequest $request): JsonResponse
    {
        $data = $request->validated();
        $cart = $this->cartService->getOrCreate($request->user());

        return ApiResponse::success([
            'dates' => $this->fulfillmentService->availableDates($cart, $data['delivery_type']),
            'earliest_at' => $this->fulfillmentService->earliestDateTime($cart)->toIso8601String(),
        ]);
    }

    public function slots(SlotsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $cart = $this->cartService->getOrCreate($request->user());

        return ApiResponse::success([
            'slots' => $this->fulfillmentService->availableSlots($data['date'], $data['delivery_type'], $cart),
        ]);
    }
}
