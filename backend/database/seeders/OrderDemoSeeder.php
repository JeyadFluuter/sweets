<?php

namespace Database\Seeders;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Finance\AccountingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Order::query()->where('admin_note', 'like', 'بذرة:%')->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'admin@sweets.ly')->first()
            ?? User::query()->first();
        $customers = Customer::query()->where('is_guest', false)->where('is_blocked', false)->get();
        $products = Product::query()->where('is_available', true)->get();

        if (! $admin || $customers->count() < 3 || $products->count() < 3) {
            $this->command?->warn('تخطي OrderDemoSeeder: بيانات أساسية ناقصة.');

            return;
        }

        // 1) طلبات مكتملة مدفوعة (للداشبورد والأكثر طلباً)
        foreach (range(1, 8) as $i) {
            $customer = $customers[$i % $customers->count()];
            $picked = $products->random(min(2, $products->count()));
            $date = now('Africa/Tripoli')->subDays(max(1, 8 - $i))->toDateString();

            $this->createHistoricalOrder(
                $admin,
                $customer,
                $picked,
                $date,
                '18:00',
                OrderStatus::Completed,
                PaymentStatus::Paid,
                fullPay: true,
                note: "بذرة: مكتمل #{$i}"
            );
        }

        // 2) بانتظار المراجعة
        $this->createHistoricalOrder(
            $admin,
            $customers[1],
            $products->take(2),
            now('Africa/Tripoli')->addDays(3)->toDateString(),
            '16:00',
            OrderStatus::PendingReview,
            PaymentStatus::Unpaid,
            fullPay: false,
            note: 'بذرة: بانتظار المراجعة'
        );

        // 3) مؤكد مع عربون
        $this->createHistoricalOrder(
            $admin,
            $customers[2],
            $products->skip(2)->take(2),
            now('Africa/Tripoli')->addDays(1)->toDateString(),
            '20:00',
            OrderStatus::Confirmed,
            PaymentStatus::Partial,
            fullPay: false,
            note: 'بذرة: عربون جزئي',
            depositPercent: 40
        );

        // 4) توصيل غداً مدفوع
        $this->createHistoricalOrder(
            $admin,
            $customers[3 % $customers->count()],
            $products->take(1),
            now('Africa/Tripoli')->addDay()->toDateString(),
            '17:00',
            OrderStatus::Confirmed,
            PaymentStatus::Paid,
            fullPay: true,
            note: 'بذرة: توصيل غداً',
            delivery: true
        );

        // 5) اليوم: تحضير + جاهز
        $this->createHistoricalOrder(
            $admin,
            $customers[4 % $customers->count()],
            $products->skip(1)->take(2),
            now('Africa/Tripoli')->toDateString(),
            '19:00',
            OrderStatus::Preparing,
            PaymentStatus::Paid,
            fullPay: true,
            note: 'بذرة: قيد التحضير اليوم'
        );

        $this->createHistoricalOrder(
            $admin,
            $customers[5 % $customers->count()],
            $products->skip(3)->take(1),
            now('Africa/Tripoli')->toDateString(),
            '12:00',
            OrderStatus::Ready,
            PaymentStatus::Paid,
            fullPay: true,
            note: 'بذرة: جاهز اليوم'
        );

        $this->command?->info('تم إنشاء طلبات تجريبية متنوعة.');
    }

    protected function createHistoricalOrder(
        User $admin,
        Customer $customer,
        $products,
        string $date,
        string $time,
        OrderStatus $status,
        PaymentStatus $paymentStatus,
        bool $fullPay,
        string $note,
        bool $delivery = false,
        ?int $depositPercent = null,
    ): ?Order {
        return DB::transaction(function () use (
            $admin, $customer, $products, $date, $time, $status, $paymentStatus,
            $fullPay, $note, $delivery, $depositPercent
        ) {
            $lines = [];
            $subtotal = 0.0;

            foreach ($products as $product) {
                $qty = random_int(1, 3);
                $unit = (float) $product->effectivePrice();
                $line = round($unit * $qty, 2);
                $subtotal += $line;
                $lines[] = [
                    'item_type' => 'product',
                    'item_id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'line_total' => $line,
                    'lead_time_hours' => (int) $product->lead_time_hours,
                ];
            }

            $deliveryFee = $delivery ? 10.0 : 0.0;
            $total = $subtotal + $deliveryFee;
            $paid = 0.0;
            if ($fullPay) {
                $paid = $total;
            } elseif ($depositPercent) {
                $paid = round($total * $depositPercent / 100, 2);
            }
            $remaining = max(0, round($total - $paid, 2));

            $order = Order::query()->create([
                'number' => 'SW-DEMO-'.now()->format('ymd').'-'.str_pad((string) (Order::query()->count() + 1), 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'delivery_type' => $delivery ? DeliveryType::Delivery : DeliveryType::Pickup,
                'fulfillment_date' => $date,
                'fulfillment_time' => $time,
                'address_text' => $delivery ? 'حي الأندلس، طرابلس' : null,
                'delivery_fee' => $deliveryFee,
                'recipient_is_self' => true,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $total,
                'payment_plan' => $fullPay ? 100 : ($depositPercent ?: null),
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'payment_status' => $paymentStatus,
                'status' => $status,
                'capacity_reserved' => $status->reservesCapacity(),
                'capacity_confirmed' => $status->confirmsCapacity(),
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'admin_note' => $note,
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            $order->statusLogs()->create([
                'from_status' => null,
                'to_status' => $status->value,
                'actor_type' => User::class,
                'actor_id' => $admin->id,
                'reason' => 'بذرة تجريبية',
            ]);

            if ($paid > 0) {
                $payment = $order->payments()->create([
                    'type' => $fullPay ? 'advance' : 'advance',
                    'method' => 'cash',
                    'amount' => $paid,
                    'status' => 'paid',
                    'reference' => 'SEED-'.strtoupper(substr(md5($order->number), 0, 8)),
                    'gateway' => 'manual',
                    'paid_at' => now()->subHours(random_int(1, 48)),
                    'recorded_by' => $admin->id,
                ]);

                $accounting = app(AccountingService::class);
                if ($accounting->readyPublic() && $status !== OrderStatus::PendingReview) {
                    $accounting->postOrderReceivable($order, $admin);
                    $accounting->postPayment($payment->fresh('order'), $admin);
                }
            } elseif ($status !== OrderStatus::PendingReview && $status !== OrderStatus::Rejected) {
                $accounting = app(AccountingService::class);
                if ($accounting->readyPublic()) {
                    $accounting->postOrderReceivable($order, $admin);
                }
            }

            return $order->fresh(['items', 'payments']);
        });
    }
}
