<?php

namespace App\Filament\Resources\AiConversationResource\Pages;

use App\Filament\Pages\AiSettings;
use App\Filament\Resources\AiConversationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAiConversations extends ListRecords
{
    protected static string $resource = AiConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('settings')
                ->label('إعدادات الذكاء')
                ->icon('heroicon-o-cog-6-tooth')
                ->url(AiSettings::getUrl()),
        ];
    }
}
