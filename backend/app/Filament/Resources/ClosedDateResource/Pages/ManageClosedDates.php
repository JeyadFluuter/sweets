<?php

namespace App\Filament\Resources\ClosedDateResource\Pages;

use App\Filament\Resources\ClosedDateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageClosedDates extends ManageRecords
{
    protected static string $resource = ClosedDateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة يوم إغلاق')];
    }
}
