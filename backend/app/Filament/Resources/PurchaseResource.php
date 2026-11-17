<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use App\Models\StockItem;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'فواتير الشراء';

    protected static ?string $modelLabel = 'فاتورة شراء';

    protected static ?string $pluralModelLabel = 'فواتير الشراء';

    protected static ?string $navigationGroup = AdminNavigation::INVENTORY;

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الفاتورة')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\Select::make('supplier_id')
                        ->label('المورد')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    Forms\Components\DatePicker::make('purchased_at')
                        ->label('تاريخ الشراء')
                        ->default(now())
                        ->required()
                        ->native(false),
                    Forms\Components\Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(2)
                        ->columnSpanFull()
                        ->placeholder('اختياري — رقم فاتورة المورد أو تفاصيل إضافية'),
                ])->columns(2),

            Forms\Components\Section::make('أصناف الفاتورة')
                ->icon('heroicon-o-queue-list')
                ->description('كل سطر يزيد كمية المخزون تلقائياً عند الحفظ')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\Select::make('stock_item_id')
                                ->label('الصنف')
                                ->options(fn () => StockItem::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->native(false),
                            Forms\Components\TextInput::make('quantity')
                                ->label('الكمية')
                                ->numeric()
                                ->required()
                                ->minValue(0.001),
                            Forms\Components\TextInput::make('unit_cost')
                                ->label('تكلفة الوحدة')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->suffix('د.ل'),
                        ])
                        ->columns(3)
                        ->minItems(1)
                        ->required()
                        ->addActionLabel('إضافة صنف')
                        ->defaultItems(1)
                        ->columnSpanFull(),
                ])
                ->visibleOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['supplier'])->withCount('items'))
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('الرقم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchased_at')
                    ->label('التاريخ')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('الأصناف')
                    ->counts('items')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money('LYD')
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'confirmed' => 'مؤكدة',
                        'draft' => 'مسودة',
                        'cancelled' => 'ملغاة',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'confirmed' => 'success',
                        'draft' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()->label('تفاصيل'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'view' => Pages\ViewPurchase::route('/{record}'),
        ];
    }
}
