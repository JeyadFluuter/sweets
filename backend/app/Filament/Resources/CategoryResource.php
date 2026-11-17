<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'الأقسام';

    protected static ?string $modelLabel = 'قسم';

    protected static ?string $pluralModelLabel = 'الأقسام';

    protected static ?string $navigationGroup = AdminNavigation::CATALOG;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('صورة القسم')
                ->description('للأقسام الرئيسية والفروع — تُحفظ كملف في التخزين.')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->label('الصورة')
                        ->image()
                        ->disk('public')
                        ->directory('categories')
                        ->visibility('public')
                        ->imageEditor()
                        ->imageCropAspectRatio('1:1')
                        ->maxSize(4096)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->helperText('مربع مفضّل — يظهر في الرئيسية وشاشة الأقسام')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('البيانات')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('الاسم')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state ?: '') ?: 'category-'.Str::random(6))),
                    Forms\Components\TextInput::make('slug')->label('المعرّف')->required()->unique(ignoreRecord: true),
                    Forms\Components\Select::make('parent_id')
                        ->label('القسم الأب (للفرع)')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText('اتركه فارغاً للقسم الرئيسي'),
                    Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
                    Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('الصورة')
                    ->getStateUsing(fn (Category $record) => $record->getFirstMediaUrl('image') ?: null)
                    ->circular(),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('parent.name')->label('القسم الأب')->placeholder('رئيسي'),
                Tables\Columns\TextColumn::make('sort_order')->label('الترتيب')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('المنتجات'),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->mutateRecordDataUsing(function (array $data, Category $record): array {
                        $media = $record->getFirstMedia('image');
                        if ($media) {
                            $data['image'] = $media->getPathRelativeToRoot();
                        }

                        return $data;
                    })
                    ->using(fn (Category $record, array $data) => app(CategoryService::class)->update($record, $data)),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->using(fn (Category $record) => app(CategoryService::class)->delete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
