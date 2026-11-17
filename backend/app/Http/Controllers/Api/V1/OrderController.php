<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\PayOrderRequest;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('customer_id', $request->user()->id)
            ->with(['items', 'payments'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::success(OrderResource::collection($orders)->response()->getData(true));
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        return ApiResponse::success(new OrderResource($order->load(['items', 'payments', 'statusLogs', 'rejectionReason'])));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->placeFromCart($request->user(), $request->validated());

        return ApiResponse::success(new OrderResource($order), 'تم إرسال الطلب للمراجعة', 201);
    }

    public function pay(PayOrderRequest $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        $payment = $this->paymentService->initiateAdvance($order, (int) $request->validated('payment_plan'));

        return ApiResponse::success([
            'payment' => new PaymentResource($payment),
            'order' => new OrderResource($payment->order->load(['items', 'payments', 'statusLogs'])),
        ], 'تم إنشاء طلب الدفع');
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        $order = $this->orderService->cancelByCustomer($order, $request->user());

        return ApiResponse::success(new OrderResource($order), 'تم إلغاء الطلب');
    }
}
