<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';

    protected static ?string $navigationLabel = 'المنتجات';

    protected static ?string $modelLabel = 'منتج';

    protected static ?string $pluralModelLabel = 'المنتجات';

    protected static ?string $navigationGroup = AdminNavigation::CATALOG;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('صور الصنف')
                ->description('تُحفظ الصور كملفات في التخزين وتظهر في التطبيق.')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\FileUpload::make('images')
                        ->label('الصور')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->maxFiles(8)
                        ->disk('public')
                        ->directory('products')
                        ->visibility('public')
                        ->imageEditor()
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->helperText('يمكن رفع عدة صور — الأولى هي الرئيسية في الكروت')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('البيانات الأساسية')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الاسم')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state ?: '') ?: 'product-'.Str::random(6))),
                Forms\Components\TextInput::make('slug')->label('المعرّف')->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('category_id')
                    ->label('القسم')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('tags')
                    ->label('الوسوم')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Forms\Components\Textarea::make('description')->label('الوصف')->rows(3)->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('التسعير والتوفر')->schema([
                Forms\Components\TextInput::make('price')->label('السعر')->numeric()->required()->prefix('د.ل'),
                Forms\Components\TextInput::make('discount_price')->label('سعر التخفيض')->numeric()->prefix('د.ل'),
                Forms\Components\DateTimePicker::make('discount_starts_at')->label('بداية التخفيض'),
                Forms\Components\DateTimePicker::make('discount_ends_at')->label('نهاية التخفيض'),
                Forms\Components\Toggle::make('is_available')->label('متاح')->default(true),
                Forms\Components\Toggle::make('ai_suggestable')->label('قابل لاقتراح الذكاء الاصطناعي')->default(true),
                Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
            ])->columns(2),

            Forms\Components\Section::make('حدود الكمية للطلب')
                ->description('أقل وأكثر كمية يمكن للزبون طلبها من هذا الصنف في الطلب الواحد.')
                ->icon('heroicon-o-shopping-bag')
                ->schema([
                    Forms\Components\TextInput::make('min_order_qty')
                        ->label('أقل كمية')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->helperText('الحد الأدنى عند الإضافة للسلة'),
                    Forms\Components\TextInput::make('max_order_qty')
                        ->label('أكثر كمية')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->default(99)
                        ->required()
                        ->gte('min_order_qty')
                        ->helperText('الحد الأقصى المسموح لكل صنف'),
                ])->columns(2),

            Forms\Components\Section::make('المكونات والتنبيهات')
                ->description('تظهر في صفحة تفاصيل الصنف داخل التطبيق إن وُجدت.')
                ->icon('heroicon-o-beaker')
                ->schema([
                    Forms\Components\Textarea::make('ingredients')
                        ->label('المكونات')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('allergen_alerts')
                        ->label('تنبيهات الحساسية')
                        ->rows(2)
                        ->helperText('مثال: يحتوي على مكسرات / جلوتين')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('الصورة')
                    ->getStateUsing(fn (Product $record) => $record->getFirstMediaUrl('images') ?: null)
                    ->square(),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('القسم'),
                Tables\Columns\TextColumn::make('price')->label('السعر')->money('LYD'),
                Tables\Columns\TextColumn::make('discount_price')->label('التخفيض')->money('LYD')->placeholder('—'),
                Tables\Columns\TextColumn::make('min_order_qty')->label('أقل كمية')->toggleable(),
                Tables\Columns\TextColumn::make('max_order_qty')->label('أكثر كمية')->toggleable(),
                Tables\Columns\IconColumn::make('is_available')->label('متاح')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')->label('القسم')->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_available')->label('متاح'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
