<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Filament\Resources\DeliverySlotResource\Pages;
use App\Models\DeliverySlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DeliverySlotResource extends Resource
{
    protected static ?string $model = DeliverySlot::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'فترات التسليم';

    protected static ?string $modelLabel = 'فترة';

    protected static ?string $pluralModelLabel = 'فترات التسليم';

    protected static ?string $navigationGroup = AdminNavigation::SCHEDULE;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TimePicker::make('start_time')->label('من')->required(),
            Forms\Components\TimePicker::make('end_time')->label('إلى')->required(),
            Forms\Components\TextInput::make('max_orders')->label('أقصى طلبات')->numeric()->default(10),
            Forms\Components\Select::make('type')->label('النوع')->options([
                'both' => 'الكل',
                'pickup' => 'استلام',
                'delivery' => 'توصيل',
            ])->required(),
            Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('start_time')->label('من'),
            Tables\Columns\TextColumn::make('end_time')->label('إلى'),
            Tables\Columns\TextColumn::make('max_orders')->label('السعة'),
            Tables\Columns\TextColumn::make('type')->label('النوع'),
            Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make()->label('تعديل'),
            Tables\Actions\DeleteAction::make()->label('حذف'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDeliverySlots::route('/')];
    }
}
