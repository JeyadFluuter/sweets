<?php

namespace App\Filament\Widgets;

use App\Services\Finance\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $dash = app(ReportService::class)->dashboard();
        $books = $dash['books'] ?? null;
        $finance = $dash['finance_month'];
        $profit = $finance['revenue'] - $finance['purchases'] - $finance['expenses'];

        return [
            Stat::make('مبيعات اليوم', number_format($dash['sales_today'], 2).' د.ل')
                ->description('هذا الشهر: '.number_format($dash['sales_month'], 2).' د.ل')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart(collect($dash['week_sales'])->pluck('amount')->all())
                ->color('success'),
            Stat::make('بانتظار المراجعة', $dash['pending_review'])
                ->description('طلبات اليوم / غداً: '.$dash['orders_today'].' / '.$dash['orders_tomorrow'])
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning'),
            Stat::make('المتبقي للتحصيل', number_format($dash['receivables'], 2).' د.ل')
                ->description($books ? 'ذمم دفترية: '.number_format($books['receivables'], 2).' د.ل' : 'من الطلبات الجارية')
                ->icon('heroicon-o-currency-dollar')
                ->color('danger'),
            Stat::make('صافي الشهر', number_format($profit, 2).' د.ل')
                ->description('إيراد − مشتريات − مصروفات')
                ->icon('heroicon-o-calculator')
                ->color($profit >= 0 ? 'success' : 'danger'),
            Stat::make('نقدية + بنك', $books
                ? number_format($books['cash'] + $books['bank'], 2).' د.ل'
                : '—')
                ->description($books ? 'صندوق '.number_format($books['cash'], 2).' | بنك '.number_format($books['bank'], 2) : 'فعّل دليل الحسابات')
                ->icon('heroicon-o-banknotes'),
            Stat::make('مخزون منخفض', count($dash['low_stock']))
                ->description(count($dash['low_stock']) ? collect($dash['low_stock'])->pluck('name')->take(3)->join('، ') : 'الكل ضمن الحد')
                ->icon('heroicon-o-exclamation-triangle')
                ->color(count($dash['low_stock']) ? 'warning' : 'success'),
        ];
    }
}
