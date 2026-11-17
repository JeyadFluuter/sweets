<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Enums\CouponScope;
use App\Enums\CouponType;
use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'الكوبونات';

    protected static ?string $modelLabel = 'كوبون';

    protected static ?string $pluralModelLabel = 'الكوبونات';

    protected static ?string $navigationGroup = AdminNavigation::MARKETING;

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('الكود')->required()->unique(ignoreRecord: true)->extraInputAttributes(['style' => 'text-transform:uppercase']),
            Forms\Components\Select::make('type')
                ->label('النوع')
                ->options(collect(CouponType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                ->required()
                ->live(),
            Forms\Components\TextInput::make('value')->label('القيمة')->numeric()->required(),
            Forms\Components\TextInput::make('max_discount')->label('أقصى خصم')->numeric()->visible(fn (Forms\Get $get) => $get('type') === CouponType::Percent->value),
            Forms\Components\TextInput::make('min_order_amount')->label('الحد الأدنى للطلب')->numeric()->default(0),
            Forms\Components\TextInput::make('usage_limit')->label('حد الاستخدام العام')->numeric(),
            Forms\Components\TextInput::make('usage_limit_per_customer')->label('حد الاستخدام لكل عميل')->numeric(),
            Forms\Components\Select::make('scope')
                ->label('نطاق التطبيق')
                ->options(collect(CouponScope::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                ->required()
                ->live(),
            Forms\Components\Select::make('categories')
                ->label('الأقسام')
                ->relationship('categories', 'name')
                ->multiple()
                ->preload()
                ->visible(fn (Forms\Get $get) => $get('scope') === CouponScope::Categories->value),
            Forms\Components\Select::make('products')
                ->label('المنتجات')
                ->relationship('products', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->visible(fn (Forms\Get $get) => $get('scope') === CouponScope::Products->value),
            Forms\Components\DateTimePicker::make('starts_at')->label('يبدأ في'),
            Forms\Components\DateTimePicker::make('ends_at')->label('ينتهي في'),
            Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الكود')->searchable()->badge(),
                Tables\Columns\TextColumn::make('type')->label('النوع')->formatStateUsing(fn ($state) => $state?->label()),
                Tables\Columns\TextColumn::make('value')->label('القيمة'),
                Tables\Columns\TextColumn::make('used_count')->label('المستخدم'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
                Tables\Columns\TextColumn::make('ends_at')->label('ينتهي')->dateTime('Y-m-d'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCoupons::route('/'),
        ];
    }
}
