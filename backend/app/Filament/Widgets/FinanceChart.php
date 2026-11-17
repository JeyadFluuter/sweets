<?php

namespace App\Filament\Widgets;

use App\Services\Finance\ReportService;
use Filament\Widgets\ChartWidget;

class FinanceChart extends ChartWidget
{
    protected static ?string $heading = 'ملخص المالية هذا الشهر';

    protected static ?string $description = 'إيرادات محصّلة مقابل مشتريات ومصروفات';

    protected static ?int $sort = 5;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $f = app(ReportService::class)->dashboard()['finance_month'];

        return [
            'datasets' => [
                [
                    'label' => 'المبلغ (د.ل)',
                    'data' => [$f['revenue'], $f['purchases'], $f['expenses']],
                    'backgroundColor' => ['#16a34a', '#d97706', '#dc2626'],
                ],
            ],
            'labels' => ['إيرادات', 'مشتريات', 'مصروفات'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }
}
