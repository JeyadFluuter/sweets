<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Purchase\StorePurchaseRequest;
use App\Http\Resources\Api\V1\PurchaseResource;
use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use App\Services\Purchases\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchaseService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Purchase::class);

        $items = Purchase::query()->with(['supplier', 'items.stockItem'])->latest()->paginate(20);

        return ApiResponse::success(PurchaseResource::collection($items)->response()->getData(true));
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $purchase = $this->purchaseService->create($request->validated(), $request->user());

        return ApiResponse::success(new PurchaseResource($purchase), 'تم تسجيل فاتورة الشراء', 201);
    }
}
