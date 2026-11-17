<?php

namespace App\Filament\Resources\NotificationTemplateResource\Pages;

use App\Filament\Resources\NotificationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageNotificationTemplates extends ManageRecords
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('قالب جديد')
                ->modalHeading('إنشاء قالب إشعار')
                ->modalWidth('3xl')
                ->slideOver(),
        ];
    }
}
