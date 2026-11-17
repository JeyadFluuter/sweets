<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClosedDateResource\Pages;
use App\Models\ClosedDate;
use App\Support\AdminNavigation;
use Filament\Resources\Resource;

/**
 * أُلغيت من الواجهة: أيام الأسبوع المغلقة تُدار من «ساعات العمل» (is_closed).
 */
class ClosedDateResource extends Resource
{
    protected static ?string $model = ClosedDate::class;

    protected static ?string $navigationGroup = AdminNavigation::SCHEDULE;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageClosedDates::route('/')];
    }
}
