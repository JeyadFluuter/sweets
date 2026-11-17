<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiConversationResource\Pages;
use App\Models\AiConversation;
use App\Support\AdminNavigation;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AiConversationResource extends Resource
{
    protected static ?string $model = AiConversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'محادثات الذكاء';

    protected static ?string $modelLabel = 'محادثة';

    protected static ?string $pluralModelLabel = 'محادثات الذكاء';

    protected static ?string $navigationGroup = AdminNavigation::INTEGRATIONS;

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('customer.phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->limit(40)
                    ->searchable()
                    ->placeholder('بدون عنوان'),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('الرسائل')
                    ->counts('messages')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر نشاط')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_messages')
                    ->label('فيها رسائل')
                    ->query(fn ($query) => $query->whereHas('messages')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('التفاصيل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        // التفاصيل تُعرض عبر واجهة شات مخصصة في ViewAiConversation
        return $infolist->schema([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAiConversations::route('/'),
            'view' => Pages\ViewAiConversation::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['customer', 'messages']);
    }
}
