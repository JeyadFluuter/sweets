<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'الموردون';

    protected static ?string $modelLabel = 'مورد';

    protected static ?string $pluralModelLabel = 'الموردون';

    protected static ?string $navigationGroup = AdminNavigation::INVENTORY;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('الاسم')->required()->maxLength(255),
            Forms\Components\TextInput::make('phone')->label('الهاتف')->tel(),
            Forms\Components\TextInput::make('email')->label('البريد')->email(),
            Forms\Components\Textarea::make('address')->label('العنوان')->rows(2)->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
            Tables\Columns\TextColumn::make('phone')->label('الهاتف'),
            Tables\Columns\TextColumn::make('email')->label('البريد'),
            Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make()->label('تعديل'),
            Tables\Actions\DeleteAction::make()->label('حذف'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSuppliers::route('/')];
    }
}
