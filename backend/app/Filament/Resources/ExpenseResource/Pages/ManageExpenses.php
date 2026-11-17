<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Services\Finance\ExpenseService;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageExpenses extends ManageRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة مصروف')
                ->using(function (array $data) {
                    return app(ExpenseService::class)->create($data, auth()->user());
                }),
        ];
    }
}
