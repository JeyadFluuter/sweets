<?php

namespace App\Filament\Resources\AiConversationResource\Pages;

use App\Filament\Resources\AiConversationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAiConversation extends ViewRecord
{
    protected static string $resource = AiConversationResource::class;

    protected static string $view = 'filament.resources.ai-conversation-resource.pages.view-ai-conversation';

    protected static ?string $title = 'تفاصيل المحادثة';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف المحادثة'),
        ];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->record->load(['customer', 'messages']);
    }
}
