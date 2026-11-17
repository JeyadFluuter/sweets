<?php

namespace App\Filament\Widgets;

use App\Services\Finance\ReportService;
use Filament\Widgets\Widget;

class BestSellersTable extends Widget
{
    protected static string $view = 'filament.widgets.best-sellers-table';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $dash = app(ReportService::class)->dashboard();

        return [
            'items' => $dash['best_sellers'],
            'lowStock' => $dash['low_stock'],
            'books' => $dash['books'],
        ];
    }
}
