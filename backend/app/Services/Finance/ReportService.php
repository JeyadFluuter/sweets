<?php

namespace App\Services\Finance;

use App\Enums\OrderStatus;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\StockItem;
use Carbon\Carbon;

class ReportService
{
    public function __construct(private readonly AccountingService $accountingService) {}

    public function dashboard(): array
    {
        $today = now('Africa/Tripoli')->toDateString();
        $tomorrow = now('Africa/Tripoli')->addDay()->toDateString();
        $monthStart = now('Africa/Tripoli')->startOfMonth()->toDateString();
        $monthEnd = now('Africa/Tripoli')->endOfMonth()->toDateString();
        $books = $this->accountingService->readyPublic()
            ? $this->accountingService->trialBalanceSummary($monthStart, $monthEnd)
            : null;

        return [
            'sales_today' => (float) Payment::query()->where('status', 'paid')->whereDate('paid_at', $today)->sum('amount'),
            'sales_month' => (float) Payment::query()->where('status', 'paid')->whereBetween('paid_at', [$monthStart, now()])->sum('amount'),
            'orders_today' => Order::query()->whereDate('fulfillment_date', $today)->count(),
            'orders_tomorrow' => Order::query()->whereDate('fulfillment_date', $tomorrow)->count(),
            'pending_review' => Order::query()->where('status', OrderStatus::PendingReview)->count(),
            'receivables' => (float) Order::query()
                ->whereIn('status', [
                    OrderStatus::Confirmed->value,
                    OrderStatus::Preparing->value,
                    OrderStatus::Ready->value,
                    OrderStatus::OutForDelivery->value,
                    OrderStatus::AwaitingPayment->value,
                ])
                ->sum('remaining_amount'),
            'low_stock' => StockItem::query()->get()->filter->isLow()->values()->map(fn (StockItem $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'quantity' => $i->quantity,
                'min_quantity' => $i->min_quantity,
            ]),
            'week_sales' => $this->weekSales(),
            'best_sellers' => $this->bestSellers(now('Africa/Tripoli')->subDays(30)->toDateString(), $today, 8),
            'orders_by_status' => $this->ordersByStatus(),
            'finance_month' => [
                'revenue' => (float) Payment::query()->where('status', 'paid')->whereBetween('paid_at', [$monthStart, now()])->sum('amount'),
                'purchases' => (float) Purchase::query()->whereBetween('purchased_at', [$monthStart, $monthEnd])->sum('total'),
                'expenses' => (float) Expense::query()->whereBetween('spent_at', [$monthStart, $monthEnd])->sum('amount'),
            ],
            'books' => $books,
        ];
    }

    public function summary(string $from, string $to): array
    {
        $revenue = (float) Payment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, Carbon::parse($to)->endOfDay()])
            ->sum('amount');

        $purchases = (float) Purchase::query()
            ->whereBetween('purchased_at', [$from, $to])
            ->sum('total');

        $expenses = (float) Expense::query()
            ->whereBetween('spent_at', [$from, $to])
            ->sum('amount');

        return [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'purchases' => $purchases,
            'expenses' => $expenses,
            'profit' => $revenue - $purchases - $expenses,
            'best_sellers' => $this->bestSellers($from, $to, 10),
            'receivables' => (float) Order::query()->sum('remaining_amount'),
            'books' => $this->accountingService->readyPublic()
                ? $this->accountingService->trialBalanceSummary($from, $to)
                : null,
        ];
    }

    public function bestSellers(string $from, string $to, int $limit = 10): array
    {
        return OrderItem::query()
            ->selectRaw('name, item_type, item_id, SUM(quantity) as qty, SUM(line_total) as total')
            ->whereHas('order', function ($q) use ($from, $to) {
                $q->whereIn('status', [
                    OrderStatus::Confirmed->value,
                    OrderStatus::Preparing->value,
                    OrderStatus::Ready->value,
                    OrderStatus::OutForDelivery->value,
                    OrderStatus::Completed->value,
                ])->whereBetween('fulfillment_date', [$from, $to]);
            })
            ->groupBy('name', 'item_type', 'item_id')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'item_type' => $row->item_type,
                'item_id' => $row->item_id,
                'qty' => (int) $row->qty,
                'total' => (float) $row->total,
            ])
            ->all();
    }

    public function ordersByStatus(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $result = [];
        foreach (OrderStatus::cases() as $status) {
            $result[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'total' => (int) ($counts[$status->value] ?? 0),
            ];
        }

        return $result;
    }

    protected function weekSales(): array
    {
        $points = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now('Africa/Tripoli')->subDays($i)->toDateString();
            $points[] = [
                'date' => $day,
                'amount' => (float) Payment::query()->where('status', 'paid')->whereDate('paid_at', $day)->sum('amount'),
                'orders' => Order::query()->whereDate('created_at', $day)->count(),
            ];
        }

        return $points;
    }
}
