<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockItemResource\Pages;
use App\Models\StockItem;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockItemResource extends Resource
{
    protected static ?string $model = StockItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'المخزون';

    protected static ?string $modelLabel = 'صنف مخزون';

    protected static ?string $pluralModelLabel = 'أصناف المخزون';

    protected static ?string $navigationGroup = AdminNavigation::INVENTORY;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الصنف')
                ->icon('heroicon-o-identification')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('الاسم')
                        ->required()
                        ->maxLength(120),
                    Forms\Components\TextInput::make('unit')
                        ->label('وحدة القياس')
                        ->default('kg')
                        ->required()
                        ->maxLength(20)
                        ->helperText('مثال: kg · لتر · علبة'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true)
                        ->inline(false),
                ])->columns(3),
            Forms\Components\Section::make('الكميات')
                ->icon('heroicon-o-scale')
                ->schema([
                    Forms\Components\TextInput::make('quantity')
                        ->label('الكمية الحالية')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->minValue(0)
                        ->helperText('يُفضَّل تعديل الكمية عبر فواتير الشراء أو الحركات'),
                    Forms\Components\TextInput::make('min_quantity')
                        ->label('حد التنبيه')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->minValue(0)
                        ->helperText('يظهر تنبيه عندما تصل الكمية لهذا الحد أو أقل'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('movements'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الصنف')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('unit')
                    ->label('الوحدة')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable()
                    ->formatStateUsing(fn ($state, StockItem $record) => number_format((float) $state, 3).' '.$record->unit)
                    ->color(fn (StockItem $record) => $record->isLow() ? 'danger' : 'success')
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('min_quantity')
                    ->label('حد التنبيه')
                    ->formatStateUsing(fn ($state, StockItem $record) => number_format((float) $state, 3)),
                Tables\Columns\TextColumn::make('stock_badge')
                    ->label('الحالة')
                    ->badge()
                    ->state(fn (StockItem $record) => $record->isLow() ? 'منخفض' : 'كافٍ')
                    ->color(fn (StockItem $record) => $record->isLow() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('movements_count')
                    ->label('الحركات')
                    ->counts('movements')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('نشط'),
                Tables\Filters\Filter::make('low')
                    ->label('مخزون منخفض')
                    ->query(fn (Builder $q) => $q->whereColumn('quantity', '<=', 'min_quantity')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('تفاصيل'),
                Tables\Actions\EditAction::make()->label('تعديل'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockItems::route('/'),
            'view' => Pages\ViewStockItem::route('/{record}'),
        ];
    }
}
