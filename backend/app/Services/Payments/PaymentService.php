<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\Finance\AccountingService;
use App\Services\Notifications\OrderNotificationService;
use App\Services\Orders\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentManager $manager,
        private readonly OrderService $orderService,
        private readonly OrderNotificationService $notificationService,
        private readonly AccountingService $accountingService,
    ) {}

    public function initiateAdvance(Order $order, int $plan): Payment
    {
        if ($order->status !== OrderStatus::AwaitingPayment) {
            throw ValidationException::withMessages([
                'order' => ['الدفع متاح فقط بعد قبول الطلب.'],
            ]);
        }

        $min = (int) Setting::getValue('min_advance_percent', 25);
        if (! in_array($plan, [25, 50, 100], true) || $plan < $min) {
            throw ValidationException::withMessages([
                'payment_plan' => ["خطة الدفع غير مسموحة. الحد الأدنى {$min}%."],
            ]);
        }

        return DB::transaction(function () use ($order, $plan) {
            $amount = round(((float) $order->total) * $plan / 100, 2);
            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'type' => 'advance',
                'method' => 'online',
                'amount' => $amount,
                'status' => 'pending',
                'reference' => 'PAY-'.Str::upper(Str::random(12)),
                'gateway' => config('payments.driver', 'manual'),
            ]);

            $session = $this->manager->driver()->initiate($payment);
            $payment->update([
                'reference' => $session->reference,
                'meta' => $session->meta,
            ]);

            $order->update(['payment_plan' => $plan]);

            if ($session->status === 'paid') {
                $this->markPaid($payment);
            }

            return $payment->fresh('order');
        });
    }

    public function recordManual(Order $order, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($order, $data, $user) {
            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'type' => $data['type'],
                'method' => $data['method'] ?? 'cash',
                'amount' => $data['amount'],
                'status' => 'paid',
                'reference' => $data['reference'] ?? 'MAN-'.Str::upper(Str::random(10)),
                'gateway' => 'manual',
                'paid_at' => now(),
                'recorded_by' => $user->id,
            ]);

            $this->applyPaymentToOrder($payment);
            $this->accountingService->postPayment($payment->fresh('order'), $user);

            return $payment->fresh('order');
        });
    }

    public function handleWebhook(Request $request): PaymentResult
    {
        $result = $this->manager->driver()->verifyWebhook($request);

        if (! $result->success) {
            return $result;
        }

        if ($result->idempotencyKey && Payment::query()->where('idempotency_key', $result->idempotencyKey)->exists()) {
            return $result;
        }

        $payment = Payment::query()->where('reference', $result->reference)->first();
        if ($payment && $payment->status !== 'paid') {
            $payment->update(['idempotency_key' => $result->idempotencyKey]);
            $this->markPaid($payment);
        }

        return $result;
    }

    public function markPaid(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => $payment->paid_at ?: now(),
            ]);

            $this->applyPaymentToOrder($payment->fresh());
            $this->accountingService->postPayment($payment->fresh(['order', 'recorder']));

            return $payment->fresh('order');
        });
    }

    protected function applyPaymentToOrder(Payment $payment): void
    {
        $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
        $paid = (float) $order->payments()->where('status', 'paid')->sum('amount');
        $total = (float) $order->total;
        $remaining = max(0, round($total - $paid, 2));

        $paymentStatus = match (true) {
            $remaining <= 0 => PaymentStatus::Paid,
            $paid > 0 => PaymentStatus::Partial,
            default => PaymentStatus::Unpaid,
        };

        $order->update([
            'paid_amount' => $paid,
            'remaining_amount' => $remaining,
            'payment_status' => $paymentStatus,
        ]);

        if ($payment->type === 'advance' && $order->status === OrderStatus::AwaitingPayment) {
            $this->orderService->transition($order->fresh(), OrderStatus::Confirmed, $payment->recorder, 'تأكيد الدفعة المقدمة');
        }

        $this->notificationService->paymentReceived($order->fresh(), $payment);
    }
}
