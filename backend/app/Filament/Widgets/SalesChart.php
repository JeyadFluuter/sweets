<?php

namespace App\Filament\Widgets;

use App\Services\Finance\ReportService;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'المبيعات خلال 7 أيام';

    protected static ?string $description = 'إجمالي الدفعات المستلمة يومياً';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $week = app(ReportService::class)->dashboard()['week_sales'];

        return [
            'datasets' => [
                [
                    'label' => 'المبيعات (د.ل)',
                    'data' => collect($week)->pluck('amount')->all(),
                    'borderColor' => '#d97706',
                    'backgroundColor' => 'rgba(217, 119, 6, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'عدد الطلبات',
                    'data' => collect($week)->pluck('orders')->all(),
                    'borderColor' => '#0f766e',
                    'backgroundColor' => 'rgba(15, 118, 110, 0.1)',
                    'fill' => false,
                    'tension' => 0.35,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => collect($week)->map(fn ($p) => \Carbon\Carbon::parse($p['date'])->translatedFormat('D d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'right'],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                    'grid' => ['drawOnChartArea' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
        ];
    }
}
