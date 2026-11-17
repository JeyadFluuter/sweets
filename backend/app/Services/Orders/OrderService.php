<?php

namespace App\Services\Orders;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\RejectionReason;
use App\Models\Setting;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Finance\AccountingService;
use App\Services\Fulfillment\FulfillmentService;
use App\Services\Notifications\OrderNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly FulfillmentService $fulfillmentService,
        private readonly OrderNotificationService $notificationService,
        private readonly AccountingService $accountingService,
    ) {}

    /**
     * إنشاء طلبية من لوحة التحكم نيابة عن عميل.
     *
     * @param  array{
     *   customer_id:int,
     *   delivery_type:string,
     *   fulfillment_date:string,
     *   fulfillment_time:string,
     *   items:list<array{item_type:string,item_id:int,quantity:int}>,
     *   address_text?:?string,
     *   lat?:?float,
     *   lng?:?float,
     *   recipient_is_self?:bool,
     *   recipient_name?:?string,
     *   recipient_phone?:?string,
     *   customer_note?:?string,
     *   admin_note?:?string,
     *   payment_mode?:string,
     *   deposit_amount?:float|int,
     *   payment_method?:string,
     *   payment_reference?:?string,
     *   discount?:float|int
     * }  $data
     */
    public function placeByAdmin(User $admin, array $data): Order
    {
        $customer = Customer::query()
            ->whereKey($data['customer_id'])
            ->where('is_guest', false)
            ->firstOrFail();

        $items = $data['items'] ?? [];
        if ($items === []) {
            throw ValidationException::withMessages(['items' => ['أضف عنصراً واحداً على الأقل.']]);
        }

        $paymentMode = $data['payment_mode'] ?? 'pay_later';
        if (! in_array($paymentMode, ['pay_now', 'pay_later', 'deposit'], true)) {
            throw ValidationException::withMessages(['payment_mode' => ['طريقة الدفع غير صالحة.']]);
        }

        $deliveryType = DeliveryType::from($data['delivery_type']);
        $date = $data['fulfillment_date'];
        $time = Carbon::parse($data['fulfillment_time'])->format('H:i');

        return DB::transaction(function () use ($admin, $customer, $data, $items, $deliveryType, $date, $time, $paymentMode) {
            $lines = [];
            $subtotal = 0.0;
            $cartLike = [];

            foreach ($items as $row) {
                $resolved = $this->cartService->resolveSellable($row['item_type'], (int) $row['item_id']);
                $qty = max(1, (int) $row['quantity']);
                $unit = (float) $resolved['unit_price'];
                $lineTotal = round($unit * $qty, 2);
                $subtotal += $lineTotal;
                $lines[] = [
                    'item_type' => $row['item_type'],
                    'item_id' => (int) $row['item_id'],
                    'name' => $resolved['name'],
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'line_total' => $lineTotal,
                    'lead_time_hours' => 0,
                ];
                $cartLike[] = [
                    'item_type' => $row['item_type'],
                    'item_id' => (int) $row['item_id'],
                    'quantity' => $qty,
                ];
            }

            $this->fulfillmentService->assertValid($cartLike, $deliveryType->value, $date, $time, lock: true);

            $deliveryFee = $deliveryType === DeliveryType::Delivery
                ? (float) Setting::getValue('delivery_fee', 10)
                : 0.0;
            $discount = max(0, (float) ($data['discount'] ?? 0));
            $total = max(0, $subtotal - $discount) + $deliveryFee;

            $order = Order::query()->create([
                'number' => $this->nextNumber(),
                'customer_id' => $customer->id,
                'delivery_type' => $deliveryType,
                'fulfillment_date' => $date,
                'fulfillment_time' => $time,
                'address_text' => $data['address_text'] ?? null,
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'delivery_fee' => $deliveryFee,
                'recipient_is_self' => (bool) ($data['recipient_is_self'] ?? true),
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => 0,
                'remaining_amount' => $total,
                'payment_status' => PaymentStatus::Unpaid,
                'status' => OrderStatus::PendingReview,
                'capacity_reserved' => true,
                'customer_note' => $data['customer_note'] ?? null,
                'admin_note' => $data['admin_note'] ?? 'طلب من لوحة التحكم',
                'reviewed_by' => $admin->id,
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            $this->logStatus($order, null, OrderStatus::PendingReview, $admin, 'إنشاء طلب من لوحة التحكم');
            $this->notificationService->orderSubmitted($order);

            $order = $this->accept($order->fresh(['items']), $admin);

            $payments = app(\App\Services\Payments\PaymentService::class);
            $method = $data['payment_method'] ?? 'cash';
            $reference = $data['payment_reference'] ?? null;

            if ($paymentMode === 'pay_now') {
                $payments->recordManual($order->fresh(), [
                    'type' => 'advance',
                    'amount' => (float) $order->total,
                    'method' => $method,
                    'reference' => $reference,
                ], $admin);
                $order = $order->fresh(['items', 'payments', 'statusLogs', 'customer']);
            } elseif ($paymentMode === 'deposit') {
                $deposit = round((float) ($data['deposit_amount'] ?? 0), 2);
                if ($deposit <= 0 || $deposit >= (float) $order->total) {
                    throw ValidationException::withMessages([
                        'deposit_amount' => ['مبلغ العربون يجب أن يكون أكبر من 0 وأقل من إجمالي الطلب.'],
                    ]);
                }
                $payments->recordManual($order->fresh(), [
                    'type' => 'advance',
                    'amount' => $deposit,
                    'method' => $method,
                    'reference' => $reference,
                ], $admin);
                $order = $order->fresh(['items', 'payments', 'statusLogs', 'customer']);
            } else {
                // آجل عند الاستلام: تأكيد بدون تحصيل الآن
                $order = $this->transition(
                    $order->fresh(),
                    OrderStatus::Confirmed,
                    $admin,
                    'آجل عند الاستلام'
                );
            }

            return $order->load(['items', 'statusLogs', 'payments', 'customer', 'rejectionReason']);
        });
    }

    public function placeFromCart(Customer $customer, array $data): Order
    {
        if ($customer->is_guest) {
            throw ValidationException::withMessages([
                'auth' => ['يجب تسجيل الدخول لإرسال الطلب.'],
            ]);
        }

        $cart = $this->cartService->getOrCreate($customer);
        $summary = $this->cartService->summary($cart);

        if ($summary['items'] === []) {
            throw ValidationException::withMessages(['cart' => ['السلة فارغة.']]);
        }

        $deliveryType = DeliveryType::from($data['delivery_type']);
        $date = $data['fulfillment_date'];
        $time = Carbon::parse($data['fulfillment_time'])->format('H:i');

        return DB::transaction(function () use ($customer, $cart, $summary, $data, $deliveryType, $date, $time) {
            $this->fulfillmentService->assertValid($cart, $deliveryType->value, $date, $time, lock: true);

            $deliveryFee = $deliveryType === DeliveryType::Delivery
                ? (float) Setting::getValue('delivery_fee', 10)
                : 0.0;

            $subtotal = (float) $summary['subtotal'];
            $discount = (float) $summary['discount'];
            $total = max(0, $subtotal - $discount) + $deliveryFee;

            $order = Order::query()->create([
                'number' => $this->nextNumber(),
                'customer_id' => $customer->id,
                'delivery_type' => $deliveryType,
                'fulfillment_date' => $date,
                'fulfillment_time' => $time,
                'address_text' => $data['address_text'] ?? null,
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'delivery_fee' => $deliveryFee,
                'recipient_is_self' => (bool) ($data['recipient_is_self'] ?? true),
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'coupon_id' => $cart->coupon_id,
                'total' => $total,
                'paid_amount' => 0,
                'remaining_amount' => $total,
                'payment_status' => PaymentStatus::Unpaid,
                'status' => OrderStatus::PendingReview,
                'capacity_reserved' => true,
                'customer_note' => $data['customer_note'] ?? null,
            ]);

            foreach ($summary['items'] as $line) {
                $order->items()->create([
                    'item_type' => $line['item_type'],
                    'item_id' => $line['item_id'],
                    'name' => $line['name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                    'lead_time_hours' => 0,
                ]);
            }

            $this->logStatus($order, null, OrderStatus::PendingReview, $customer, 'إرسال الطلب للمراجعة');
            $this->cartService->clear($cart);
            $this->notificationService->orderSubmitted($order);

            return $order->load(['items', 'statusLogs', 'payments', 'rejectionReason']);
        });
    }

    public function accept(Order $order, User $admin): Order
    {
        $this->assertStatus($order, OrderStatus::PendingReview);

        return DB::transaction(function () use ($order, $admin) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $cartLike = $order->items->map(fn ($i) => [
                'item_type' => $i->item_type,
                'item_id' => $i->item_id,
                'quantity' => $i->quantity,
                'lead_time_hours' => $i->lead_time_hours,
            ])->all();

            $this->fulfillmentService->assertValid(
                $cartLike,
                $order->delivery_type->value,
                $order->fulfillment_date->toDateString(),
                Carbon::parse($order->fulfillment_time)->format('H:i'),
                lock: true
            );

            $dueHours = (int) Setting::getValue('payment_due_hours', 24);
            $from = $order->status;
            $order->update([
                'status' => OrderStatus::AwaitingPayment,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'payment_due_at' => now()->addHours($dueHours),
                'capacity_reserved' => true,
            ]);

            $this->logStatus($order, $from, OrderStatus::AwaitingPayment, $admin, 'قبول الطلب');
            $this->accountingService->postOrderReceivable($order->fresh(), $admin);
            $this->notificationService->orderAccepted($order->fresh());

            return $order->fresh(['items', 'statusLogs', 'payments']);
        });
    }

    public function reject(Order $order, User $admin, int $reasonId, ?string $note = null): Order
    {
        $this->assertStatus($order, OrderStatus::PendingReview);
        RejectionReason::query()->whereKey($reasonId)->where('is_active', true)->firstOrFail();

        return DB::transaction(function () use ($order, $admin, $reasonId, $note) {
            $from = $order->status;
            $order->update([
                'status' => OrderStatus::Rejected,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason_id' => $reasonId,
                'admin_note' => $note,
                'capacity_reserved' => false,
            ]);
            $this->logStatus($order, $from, OrderStatus::Rejected, $admin, $note ?: 'رفض الطلب');
            $this->notificationService->orderRejected($order->fresh('rejectionReason'));

            return $order->fresh(['items', 'statusLogs', 'rejectionReason']);
        });
    }

    public function suggestSlot(Order $order, User $admin, string $date, string $time, ?string $note = null): Order
    {
        $this->assertStatus($order, OrderStatus::PendingReview);

        $order->update([
            'suggested_date' => $date,
            'suggested_time' => Carbon::parse($time)->format('H:i'),
            'admin_note' => $note,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->notificationService->slotSuggested($order->fresh());

        return $order->fresh();
    }

    public function transition(Order $order, OrderStatus $to, Customer|User|null $actor = null, ?string $reason = null): Order
    {
        $from = $order->status;
        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages([
                'status' => ["لا يمكن الانتقال من {$from->label()} إلى {$to->label()}."],
            ]);
        }

        if ($to === OrderStatus::Completed && $order->payment_status !== PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'status' => ['لا يمكن إكمال الطلب وفيه مبلغ متبقٍ دون تأكيد صريح بعد السداد.'],
            ]);
        }

        return DB::transaction(function () use ($order, $from, $to, $actor, $reason) {
            $order->update([
                'status' => $to,
                'capacity_confirmed' => $to->confirmsCapacity(),
                'capacity_reserved' => $to->reservesCapacity(),
            ]);
            $this->logStatus($order, $from, $to, $actor, $reason);
            $this->notificationService->statusChanged($order->fresh(), $to);

            return $order->fresh(['items', 'statusLogs', 'payments']);
        });
    }

    public function cancelByCustomer(Order $order, Customer $customer): Order
    {
        if ($order->customer_id !== $customer->id) {
            throw ValidationException::withMessages(['order' => ['غير مصرح.']]);
        }

        $hours = (int) Setting::getValue('cancel_policy_hours', 24);
        $deadline = Carbon::parse($order->fulfillment_date->toDateString().' '.$order->fulfillment_time, 'Africa/Tripoli')
            ->subHours($hours);

        if (now('Africa/Tripoli')->gt($deadline) && in_array($order->status, [
            OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Ready,
        ], true)) {
            throw ValidationException::withMessages([
                'order' => ['تجاوزت مهلة الإلغاء حسب سياسة المعمل.'],
            ]);
        }

        if (! in_array($order->status, [
            OrderStatus::PendingReview,
            OrderStatus::AwaitingPayment,
            OrderStatus::Confirmed,
        ], true)) {
            throw ValidationException::withMessages(['order' => ['لا يمكن إلغاء الطلب في حالته الحالية.']]);
        }

        return $this->transition($order, OrderStatus::Cancelled, $customer, 'إلغاء من العميل');
    }

    public function expireUnpaid(): int
    {
        $count = 0;
        Order::query()
            ->where('status', OrderStatus::AwaitingPayment)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->each(function (Order $order) use (&$count) {
                $this->transition($order, OrderStatus::Expired, null, 'انتهاء مهلة الدفع');
                $this->notificationService->paymentExpired($order->fresh());
                $count++;
            });

        return $count;
    }

    protected function assertStatus(Order $order, OrderStatus $expected): void
    {
        if ($order->status !== $expected) {
            throw ValidationException::withMessages([
                'status' => ["الطلب ليس في حالة {$expected->label()}."],
            ]);
        }
    }

    protected function logStatus(
        Order $order,
        ?OrderStatus $from,
        OrderStatus $to,
        Customer|User|null $actor,
        ?string $reason = null
    ): void {
        OrderStatusLog::query()->create([
            'order_id' => $order->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'actor_type' => $actor ? $actor::class : null,
            'actor_id' => $actor?->id,
            'reason' => $reason,
        ]);
    }

    protected function nextNumber(): string
    {
        return 'SW-'.now()->format('ymd').'-'.str_pad((string) (Order::query()->whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
