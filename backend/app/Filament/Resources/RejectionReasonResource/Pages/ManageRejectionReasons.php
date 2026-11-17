<?php

namespace App\Filament\Resources\RejectionReasonResource\Pages;

use App\Filament\Resources\RejectionReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRejectionReasons extends ManageRecords
{
    protected static string $resource = RejectionReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة سبب')];
    }
}
