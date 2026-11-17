<?php

namespace App\Services\Notifications;

use App\Enums\OrderStatus;
use App\Models\AppNotification;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\Payment;

class OrderNotificationService
{
    public function orderSubmitted(Order $order): void
    {
        $this->pushToCustomer($order, 'order_submitted', 'تم استلام طلبك', "طلبك رقم {$order->number} قيد المراجعة.");
    }

    public function orderAccepted(Order $order): void
    {
        $due = $order->payment_due_at?->timezone('Africa/Tripoli')->format('Y-m-d H:i');
        $this->pushToCustomer(
            $order,
            'order_accepted',
            'تمت الموافقة على طلبك',
            "تمت الموافقة على الطلب {$order->number}. يرجى الدفع بمبلغ {$order->total} د.ل قبل {$due}."
        );
    }

    public function orderRejected(Order $order): void
    {
        $reason = $order->rejectionReason?->name ?? 'غير محدد';
        $this->pushToCustomer($order, 'order_rejected', 'تم رفض الطلب', "تم رفض الطلب {$order->number}. السبب: {$reason}");
    }

    public function slotSuggested(Order $order): void
    {
        $this->pushToCustomer(
            $order,
            'slot_suggested',
            'اقتراح موعد بديل',
            "نقترح موعداً بديلاً لطلبك {$order->number}: {$order->suggested_date?->toDateString()} {$order->suggested_time}"
        );
    }

    public function paymentReceived(Order $order, Payment $payment): void
    {
        $msg = "تم استلام دفعة {$payment->amount} د.ل لطلب {$order->number}.";
        if ((float) $order->remaining_amount > 0) {
            $msg .= " المتبقي: {$order->remaining_amount} د.ل.";
        }
        $this->pushToCustomer($order, 'payment_received', 'تأكيد الدفعة', $msg);
    }

    public function paymentExpired(Order $order): void
    {
        $this->pushToCustomer($order, 'payment_expired', 'انتهت مهلة الدفع', "انتهت مهلة دفع الطلب {$order->number} وتم تحرير الحجز.");
    }

    public function statusChanged(Order $order, OrderStatus $status): void
    {
        $this->pushToCustomer($order, 'order_status_'.$status->value, 'تحديث حالة الطلب', "طلبك {$order->number}: {$status->label()}");
    }

    protected function pushToCustomer(Order $order, string $type, string $fallbackTitle, string $fallbackBody): void
    {
        $template = NotificationTemplate::query()->where('key', $type)->where('is_active', true)->first();
        $title = $template->title ?? $fallbackTitle;
        $body = $template
            ? str_replace(
                [':number', ':total', ':remaining', ':status'],
                [$order->number, (string) $order->total, (string) $order->remaining_amount, $order->status->label()],
                $template->body
            )
            : $fallbackBody;

        $idempotency = $type.'-'.$order->id.'-'.now()->format('YmdHi');

        if (NotificationLog::query()->where('idempotency_key', $idempotency)->exists()) {
            return;
        }

        AppNotification::query()->create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => ['order_id' => $order->id, 'number' => $order->number],
        ]);

        NotificationLog::query()->create([
            'channel' => 'in_app',
            'type' => $type,
            'notifiable_type' => $order->customer::class,
            'notifiable_id' => $order->customer_id,
            'order_id' => $order->id,
            'idempotency_key' => $idempotency,
            'status' => 'sent',
            'payload' => ['title' => $title, 'body' => $body],
        ]);

        // FCM يُفعَّل عند توفر اعتماد Firebase عبر NotificationDispatcher
        app(NotificationDispatcher::class)->sendToCustomer($order->customer, $title, $body, [
            'type' => $type,
            'order_id' => (string) $order->id,
        ], $idempotency.'-fcm', $order->id);
    }
}
