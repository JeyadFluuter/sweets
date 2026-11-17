<?php

namespace App\Filament\Resources\BannerResource\Pages;

use App\Filament\Resources\BannerResource;
use App\Services\Catalog\BannerService;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBanners extends ManageRecords
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('إضافة بانر')
                ->modalHeading('بانر / هيرو جديد')
                ->using(function (array $data) {
                    return app(BannerService::class)->create($data);
                }),
        ];
    }
}
