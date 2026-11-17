<?php

namespace App\Filament\Widgets;

use App\Services\Finance\ReportService;
use Filament\Widgets\ChartWidget;

class BestSellersChart extends ChartWidget
{
    protected static ?string $heading = 'الأكثر طلباً (30 يوماً)';

    protected static ?string $description = 'حسب الكمية المباعة للطلبات المؤكدة والمكتملة';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $items = app(ReportService::class)->dashboard()['best_sellers'];

        return [
            'datasets' => [
                [
                    'label' => 'الكمية',
                    'data' => collect($items)->pluck('qty')->all(),
                    'backgroundColor' => [
                        '#d97706', '#f59e0b', '#fbbf24', '#fcd34d',
                        '#0f766e', '#14b8a6', '#2dd4bf', '#5eead4',
                    ],
                ],
            ],
            'labels' => collect($items)->map(fn ($i) => mb_strimwidth($i['name'], 0, 18, '…'))->all() ?: ['لا بيانات'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true]],
        ];
    }
}
