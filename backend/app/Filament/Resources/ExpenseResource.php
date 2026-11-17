<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'المصروفات';

    protected static ?string $modelLabel = 'مصروف';

    protected static ?string $pluralModelLabel = 'المصروفات';

    protected static ?string $navigationGroup = AdminNavigation::FINANCE;

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('expense_category_id')
                ->label('الفئة')
                ->relationship('category', 'name')
                ->required()
                ->preload(),
            Forms\Components\TextInput::make('title')->label('العنوان')->required(),
            Forms\Components\TextInput::make('amount')->label('المبلغ')->numeric()->required()->minValue(0),
            Forms\Components\DatePicker::make('spent_at')->label('التاريخ')->default(now())->required(),
            Forms\Components\Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
            Forms\Components\Hidden::make('recorded_by')->default(fn () => auth()->id()),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('spent_at')->label('التاريخ')->date('Y-m-d')->sortable(),
            Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable(),
            Tables\Columns\TextColumn::make('category.name')->label('الفئة'),
            Tables\Columns\TextColumn::make('amount')->label('المبلغ')->money('LYD'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('تعديل'),
            Tables\Actions\DeleteAction::make()->label('حذف'),
        ])->defaultSort('spent_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageExpenses::route('/')];
    }
}
