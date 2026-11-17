<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Filament\Resources\ExpenseCategoryResource\Pages;
use App\Models\ExpenseCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'فئات المصروفات';

    protected static ?string $modelLabel = 'فئة مصروف';

    protected static ?string $pluralModelLabel = 'فئات المصروفات';

    protected static ?string $navigationGroup = AdminNavigation::FINANCE;

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('الاسم')->required()->unique(ignoreRecord: true),
            Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make()->label('تعديل'),
            Tables\Actions\DeleteAction::make()->label('حذف'),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageExpenseCategories::route('/')];
    }
}
