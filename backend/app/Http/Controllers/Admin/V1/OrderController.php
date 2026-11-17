<?php

namespace App\Http\Controllers\Admin\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Order\KitchenRequest;
use App\Http\Requests\Admin\V1\Order\RecordPaymentRequest;
use App\Http\Requests\Admin\V1\Order\RejectOrderRequest;
use App\Http\Requests\Admin\V1\Order\SuggestSlotRequest;
use App\Http\Requests\Admin\V1\Order\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\Fulfillment\FulfillmentService;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly FulfillmentService $fulfillmentService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with(['customer', 'items'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->boolean('has_remaining'), fn ($q) => $q->where('remaining_amount', '>', 0))
            ->when($request->date, fn ($q, $date) => $q->whereDate('fulfillment_date', $date))
            ->orderBy('fulfillment_date')
            ->orderBy('fulfillment_time')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success(OrderResource::collection($orders)->response()->getData(true));
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $order->load(['items', 'payments', 'statusLogs', 'rejectionReason', 'customer']);

        return ApiResponse::success([
            'order' => new OrderResource($order),
            'review' => $this->fulfillmentService->reviewIndicators($order),
        ]);
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $this->authorize('accept', $order);
        $order = $this->orderService->accept($order, $request->user());

        return ApiResponse::success(new OrderResource($order), 'تم قبول الطلب');
    }

    public function reject(RejectOrderRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();
        $order = $this->orderService->reject($order, $request->user(), (int) $data['reason_id'], $data['note'] ?? null);

        return ApiResponse::success(new OrderResource($order), 'تم رفض الطلب');
    }

    public function suggestSlot(SuggestSlotRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();
        $order = $this->orderService->suggestSlot($order, $request->user(), $data['date'], $data['time'], $data['note'] ?? null);

        return ApiResponse::success(new OrderResource($order), 'تم اقتراح موعد بديل');
    }

    public function status(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();
        $order = $this->orderService->transition(
            $order,
            OrderStatus::from($data['status']),
            $request->user(),
            $data['reason'] ?? null
        );

        return ApiResponse::success(new OrderResource($order), 'تم تحديث الحالة');
    }

    public function payments(RecordPaymentRequest $request, Order $order): JsonResponse
    {
        $payment = $this->paymentService->recordManual($order, $request->validated(), $request->user());

        return ApiResponse::success([
            'payment' => new PaymentResource($payment),
            'order' => new OrderResource($payment->order->load(['items', 'payments', 'statusLogs'])),
        ], 'تم تسجيل الدفعة', 201);
    }

    public function kitchen(KitchenRequest $request): JsonResponse
    {
        $date = $request->validated('date');

        $items = \App\Models\OrderItem::query()
            ->selectRaw('name, SUM(quantity) as quantity')
            ->whereHas('order', function ($q) use ($date) {
                $q->whereDate('fulfillment_date', $date)
                    ->whereIn('status', [
                        OrderStatus::Confirmed->value,
                        OrderStatus::Preparing->value,
                        OrderStatus::Ready->value,
                        OrderStatus::OutForDelivery->value,
                    ]);
            })
            ->groupBy('name')
            ->orderByDesc('quantity')
            ->get();

        return ApiResponse::success(['date' => $date, 'items' => $items]);
    }
}
