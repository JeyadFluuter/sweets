<?php

namespace App\Filament\Widgets;

use App\Services\Finance\ReportService;
use Filament\Widgets\ChartWidget;

class OrdersStatusChart extends ChartWidget
{
    protected static ?string $heading = 'توزيع حالات الطلبات';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $rows = collect(app(ReportService::class)->dashboard()['orders_by_status'])
            ->filter(fn ($r) => $r['total'] > 0)
            ->values();

        if ($rows->isEmpty()) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#e5e7eb']]],
                'labels' => ['لا طلبات بعد'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $rows->pluck('total')->all(),
                    'backgroundColor' => [
                        '#f59e0b', '#38bdf8', '#22c55e', '#a78bfa',
                        '#14b8a6', '#f97316', '#ef4444', '#94a3b8', '#64748b',
                    ],
                ],
            ],
            'labels' => $rows->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }
}
