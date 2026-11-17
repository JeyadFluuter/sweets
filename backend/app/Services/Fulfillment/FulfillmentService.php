<?php

namespace App\Services\Fulfillment;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Models\BusinessHour;
use App\Models\Cart;
use App\Models\Collection;
use App\Models\DeliverySlot;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\ValidationException;

class FulfillmentService
{
    /**
     * @param  Cart|array{items?: list<array{item_type?:string,item_id?:int,product_id?:int,quantity?:int,lead_time_hours?:int}>}|SupportCollection  $cart
     */
    public function earliestDateTime(Cart|array|SupportCollection $cart): Carbon
    {
        $lead = max(
            (int) Setting::getValue('min_lead_hours', 4),
            $this->cartLeadTimeHours($cart)
        );

        return now('Africa/Tripoli')->addHours($lead);
    }

    /**
     * @return list<string> Y-m-d
     */
    public function availableDates(Cart|array|SupportCollection $cart, string $type): array
    {
        $deliveryType = DeliveryType::from($type);
        $earliest = $this->earliestDateTime($cart)->startOfDay();
        $maxDays = (int) Setting::getValue('max_advance_days', 30);
        $capacity = (int) Setting::getValue('daily_capacity', 50);
        $hours = BusinessHour::query()->get()->keyBy('day_of_week');

        $dates = [];
        for ($i = 0; $i <= $maxDays; $i++) {
            $date = now('Africa/Tripoli')->startOfDay()->addDays($i);
            if ($date->lt($earliest)) {
                continue;
            }

            $key = $date->toDateString();
            $dayHour = $hours->get($date->dayOfWeek);
            // اليوم المغلق يُحدد من ساعات العمل فقط
            if (! $dayHour || $dayHour->is_closed) {
                continue;
            }

            if ($this->ordersCountForDate($key, reserved: true) >= $capacity) {
                continue;
            }

            if ($this->exceedsProductDailyLimits($cart, $key)) {
                continue;
            }

            if ($this->availableSlots($key, $deliveryType->value, $cart) === []) {
                continue;
            }

            $dates[] = $key;
        }

        return $dates;
    }

    /**
     * @return list<array{start:string,end:string,remaining:int,slot_id:int}>
     */
    public function availableSlots(string $date, string $type, Cart|array|SupportCollection $cart): array
    {
        $day = Carbon::parse($date, 'Africa/Tripoli')->startOfDay();
        $earliest = $this->earliestDateTime($cart);
        $hours = BusinessHour::query()->where('day_of_week', $day->dayOfWeek)->first();

        if (! $hours || $hours->is_closed) {
            return [];
        }

        $slots = DeliverySlot::query()
            ->where('is_active', true)
            ->where(function ($q) use ($type) {
                $q->where('type', 'both')->orWhere('type', $type);
            })
            ->orderBy('start_time')
            ->get();

        $result = [];
        foreach ($slots as $slot) {
            $start = Carbon::parse($day->toDateString().' '.$slot->start_time, 'Africa/Tripoli');
            $end = Carbon::parse($day->toDateString().' '.$slot->end_time, 'Africa/Tripoli');

            if ($hours->open_at && $start->format('H:i:s') < $hours->open_at) {
                continue;
            }
            if ($hours->close_at && $end->format('H:i:s') > $hours->close_at) {
                continue;
            }
            if ($start->lt($earliest)) {
                continue;
            }

            $used = $this->ordersCountForSlot($day->toDateString(), $slot->start_time, reserved: true);
            $remaining = max(0, (int) $slot->max_orders - $used);
            if ($remaining <= 0) {
                continue;
            }

            $result[] = [
                'slot_id' => $slot->id,
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
                'remaining' => $remaining,
            ];
        }

        return $result;
    }

    public function assertValid(
        Cart|array|SupportCollection $cart,
        string $type,
        string $date,
        string $time,
        bool $lock = false
    ): void {
        $dates = $this->availableDates($cart, $type);
        if (! in_array($date, $dates, true)) {
            throw ValidationException::withMessages([
                'fulfillment_date' => ['الموعد المحدد غير متاح. يرجى اختيار يوم آخر.'],
            ]);
        }

        $slots = $this->availableSlots($date, $type, $cart);
        $match = collect($slots)->first(function (array $slot) use ($time) {
            return $slot['start'] === Carbon::parse($time)->format('H:i');
        });

        if (! $match) {
            throw ValidationException::withMessages([
                'fulfillment_time' => ['الفترة المحددة غير متاحة أو ممتلئة.'],
            ]);
        }

        if ($lock) {
            Order::query()
                ->whereDate('fulfillment_date', $date)
                ->where('fulfillment_time', Carbon::parse($time)->format('H:i:s'))
                ->whereIn('status', $this->reservingStatuses())
                ->lockForUpdate()
                ->get();

            $slots = $this->availableSlots($date, $type, $cart);
            $match = collect($slots)->first(fn (array $slot) => $slot['start'] === Carbon::parse($time)->format('H:i'));
            if (! $match) {
                throw ValidationException::withMessages([
                    'fulfillment_time' => ['تم اكتمال سعة هذه الفترة، يرجى اختيار موعد آخر.'],
                ]);
            }
        }
    }

    public function reviewIndicators(Order $order): array
    {
        $cartLike = $order->items->map(fn ($item) => [
            'item_type' => $item->item_type,
            'item_id' => $item->item_id,
            'quantity' => $item->quantity,
            'lead_time_hours' => $item->lead_time_hours,
        ])->all();

        $warnings = [];
        try {
            $this->assertValid($cartLike, $order->delivery_type->value, $order->fulfillment_date->toDateString(), $order->fulfillment_time);
        } catch (ValidationException $e) {
            $warnings[] = collect($e->errors())->flatten()->first() ?? 'مشكلة في الموعد';
        }

        $earliest = $this->earliestDateTime($cartLike);
        $scheduled = Carbon::parse($order->fulfillment_date->toDateString().' '.$order->fulfillment_time, 'Africa/Tripoli');
        if ($scheduled->lt($earliest)) {
            $warnings[] = 'موعد التسليم أقرب من مدة التجهيز المطلوبة.';
        }

        return [
            'ok' => $warnings === [],
            'warnings' => $warnings,
            'earliest_at' => $earliest->toIso8601String(),
        ];
    }

    protected function cartLeadTimeHours(Cart|array|SupportCollection $cart): int
    {
        return 0;
    }

    protected function exceedsProductDailyLimits(Cart|array|SupportCollection $cart, string $date): bool
    {
        return false;
    }

    protected function orderedProductQty(int $productId, string $date): int
    {
        return (int) \App\Models\OrderItem::query()
            ->where('item_type', 'product')
            ->where('item_id', $productId)
            ->whereHas('order', function ($q) use ($date) {
                $q->whereDate('fulfillment_date', $date)
                    ->whereIn('status', $this->reservingStatuses());
            })
            ->sum('quantity');
    }

    protected function ordersCountForDate(string $date, bool $reserved = true): int
    {
        return Order::query()
            ->whereDate('fulfillment_date', $date)
            ->whereIn('status', $reserved ? $this->reservingStatuses() : $this->confirmedStatuses())
            ->count();
    }

    protected function ordersCountForSlot(string $date, string $startTime, bool $reserved = true): int
    {
        return Order::query()
            ->whereDate('fulfillment_date', $date)
            ->where('fulfillment_time', Carbon::parse($startTime)->format('H:i:s'))
            ->whereIn('status', $reserved ? $this->reservingStatuses() : $this->confirmedStatuses())
            ->count();
    }

    /**
     * @return list<string>
     */
    protected function reservingStatuses(): array
    {
        return array_map(
            fn (OrderStatus $s) => $s->value,
            array_filter(OrderStatus::cases(), fn (OrderStatus $s) => $s->reservesCapacity())
        );
    }

    /**
     * @return list<string>
     */
    protected function confirmedStatuses(): array
    {
        return array_map(
            fn (OrderStatus $s) => $s->value,
            array_filter(OrderStatus::cases(), fn (OrderStatus $s) => $s->confirmsCapacity())
        );
    }

    /**
     * @return list<array<string,mixed>>
     */
    protected function normalizeItems(Cart|array|SupportCollection $cart): array
    {
        if ($cart instanceof Cart) {
            return $cart->items->map(fn ($i) => [
                'item_type' => $i->item_type,
                'item_id' => $i->item_id,
                'quantity' => $i->quantity,
            ])->all();
        }

        if ($cart instanceof SupportCollection) {
            return $cart->all();
        }

        return $cart['items'] ?? $cart;
    }
}
