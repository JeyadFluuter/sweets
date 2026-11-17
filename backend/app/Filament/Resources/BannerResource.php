<?php

namespace App\Filament\Resources;

use App\Enums\BannerLinkType;
use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use App\Services\Catalog\BannerService;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'البانرات';

    protected static ?string $modelLabel = 'بانر';

    protected static ?string $pluralModelLabel = 'البانرات';

    protected static ?string $navigationGroup = AdminNavigation::MARKETING;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('صورة الهيرو')
                ->description('تُحفظ الصورة كملف في storage/app/public عبر مكتبة الوسائط.')
                ->icon('heroicon-o-photo')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->label('صورة البانر')
                        ->image()
                        ->disk('public')
                        ->directory('banners')
                        ->visibility('public')
                        ->imageEditor()
                        ->imageCropAspectRatio('16:9')
                        ->imageResizeMode('cover')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                        ->helperText('مطلوبة — JPG / PNG / WebP حتى 5MB')
                        ->required(fn (?Banner $record) => $record === null || ! $record->hasMedia('image'))
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('المحتوى والرابط')
                ->icon('heroicon-o-link')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('العنوان (اختياري)')
                        ->helperText('يظهر فوق الصورة في التطبيق إن وُجد'),
                    Forms\Components\Select::make('link_type')
                        ->label('نوع الرابط')
                        ->options(collect(BannerLinkType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->required()
                        ->native(false)
                        ->live(),
                    Forms\Components\TextInput::make('link_id')
                        ->label('معرّف الرابط')
                        ->numeric()
                        ->visible(fn (Forms\Get $get) => $get('link_type') !== BannerLinkType::None->value),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0),
                    Forms\Components\DateTimePicker::make('starts_at')->label('يبدأ في'),
                    Forms\Components\DateTimePicker::make('ends_at')->label('ينتهي في'),
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
                    ->getStateUsing(fn (Banner $record) => $record->getFirstMediaUrl('image') ?: null)
                    ->square(),
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('link_type')
                    ->label('الرابط')
                    ->formatStateUsing(fn ($state) => $state?->label()),
                Tables\Columns\TextColumn::make('sort_order')->label('الترتيب')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
                Tables\Columns\TextColumn::make('starts_at')->label('البداية')->dateTime('Y-m-d')->toggleable(),
                Tables\Columns\TextColumn::make('ends_at')->label('النهاية')->dateTime('Y-m-d')->toggleable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->mutateRecordDataUsing(function (array $data, Banner $record): array {
                        $media = $record->getFirstMedia('image');
                        if ($media) {
                            $data['image'] = $media->getPathRelativeToRoot();
                        }

                        return $data;
                    })
                    ->using(function (Banner $record, array $data): Banner {
                        return app(BannerService::class)->update($record, $data);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->using(fn (Banner $record) => app(BannerService::class)->delete($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBanners::route('/'),
        ];
    }
}
