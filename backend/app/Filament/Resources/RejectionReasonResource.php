<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Filament\Resources\RejectionReasonResource\Pages;
use App\Models\RejectionReason;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RejectionReasonResource extends Resource
{
    protected static ?string $model = RejectionReason::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?string $navigationLabel = 'أسباب الرفض';

    protected static ?string $modelLabel = 'سبب رفض';

    protected static ?string $pluralModelLabel = 'أسباب الرفض';

    protected static ?string $navigationGroup = AdminNavigation::OPERATIONS;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('السبب')->required()->maxLength(255),
            Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('السبب')->searchable(),
                Tables\Columns\TextColumn::make('sort_order')->label('الترتيب')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageRejectionReasons::route('/')];
    }
}
