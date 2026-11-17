<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Filament\Resources\CollectionResource\Pages;
use App\Models\Collection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'الباقات';

    protected static ?string $modelLabel = 'باقة';

    protected static ?string $pluralModelLabel = 'الباقات';

    protected static ?string $navigationGroup = AdminNavigation::MARKETING;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('الاسم')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state ?: '') ?: 'collection-'.Str::random(6))),
            Forms\Components\TextInput::make('slug')->label('المعرّف')->required()->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')->label('الوصف')->rows(3)->columnSpanFull(),
            Forms\Components\TextInput::make('price')->label('السعر')->numeric()->required()->prefix('د.ل'),
            Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
            Forms\Components\DateTimePicker::make('starts_at')->label('يبدأ في'),
            Forms\Components\DateTimePicker::make('ends_at')->label('ينتهي في'),
            Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
            Forms\Components\Repeater::make('items')
                ->label('محتويات الباقة')
                ->relationship()
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label('المنتج')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('quantity')->label('الكمية')->numeric()->default(1)->required(),
                ])
                ->columns(2)
                ->minItems(1)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('price')->label('السعر')->money('LYD'),
                Tables\Columns\TextColumn::make('items_count')->counts('items')->label('العناصر'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'edit' => Pages\EditCollection::route('/{record}/edit'),
        ];
    }
}
