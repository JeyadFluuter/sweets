<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'إعدادات المعمل';

    protected static ?string $modelLabel = 'إعداد';

    protected static ?string $pluralModelLabel = 'الإعدادات';

    protected static ?string $navigationGroup = AdminNavigation::SYSTEM;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('تعريف الإعداد')
                ->description('اختر مجموعة واضحة ومفتاحاً مستقراً لا يتغير بعد الحفظ.')
                ->icon('heroicon-o-identification')
                ->schema([
                    Forms\Components\Select::make('group')
                        ->label('المجموعة')
                        ->options([
                            'general' => 'عام',
                            'fulfillment' => 'التجهيز والتوصيل',
                            'payments' => 'الدفع',
                            'orders' => 'الطلبات',
                            'notifications' => 'الإشعارات',
                            'ai' => 'الذكاء الاصطناعي',
                        ])
                        ->required()
                        ->native(false)
                        ->default('general'),
                    Forms\Components\TextInput::make('key')
                        ->label('المفتاح البرمجي')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->disabledOn('edit')
                        ->helperText('مثال: delivery_fee — يُستخدم في الكود كما هو'),
                    Forms\Components\Placeholder::make('key_ar')
                        ->label('المعنى')
                        ->content(fn (Get $get) => AdminNavigation::settingKeyLabel((string) $get('key')))
                        ->visible(fn (Get $get) => filled($get('key'))),
                ])->columns(2),

            Forms\Components\Section::make('القيمة')
                ->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('نوع القيمة')
                        ->options([
                            'string' => 'نص',
                            'integer' => 'رقم صحيح',
                            'float' => 'رقم عشري',
                            'boolean' => 'نعم / لا',
                            'json' => 'JSON',
                        ])
                        ->required()
                        ->native(false)
                        ->live(),
                    Forms\Components\Toggle::make('is_sensitive')
                        ->label('قيمة حسّاسة')
                        ->helperText('تُخفى في الجداول والسجلات')
                        ->inline(false),
                    Forms\Components\TextInput::make('value')
                        ->label('القيمة')
                        ->required(fn (Get $get) => $get('type') !== 'boolean')
                        ->numeric(fn (Get $get) => in_array($get('type'), ['integer', 'float'], true))
                        ->visible(fn (Get $get) => ! in_array($get('type'), ['boolean', 'json'], true))
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('value_bool')
                        ->label('مفعّل؟')
                        ->visible(fn (Get $get) => $get('type') === 'boolean')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Toggle $component, ?Setting $record) {
                            if ($record && $record->type === 'boolean') {
                                $component->state(filter_var($record->value, FILTER_VALIDATE_BOOLEAN));
                            }
                        })
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('value_json')
                        ->label('JSON')
                        ->rows(5)
                        ->visible(fn (Get $get) => $get('type') === 'json')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Forms\Components\Textarea $component, ?Setting $record) {
                            if ($record && $record->type === 'json') {
                                $component->state($record->value);
                            }
                        })
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('المجموعة')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'fulfillment' => 'warning',
                        'payments' => 'success',
                        'orders' => 'info',
                        'ai' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => AdminNavigation::settingGroupLabel($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('key')
                    ->label('الإعداد')
                    ->searchable()
                    ->description(fn (Setting $record) => $record->key)
                    ->formatStateUsing(fn (Setting $record) => AdminNavigation::settingKeyLabel($record->key)),
                Tables\Columns\TextColumn::make('value')
                    ->label('القيمة')
                    ->limit(48)
                    ->formatStateUsing(fn ($state, Setting $record) => $record->is_sensitive ? '••••••' : (string) $state),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'string' => 'نص',
                        'integer' => 'صحيح',
                        'float' => 'عشري',
                        'boolean' => 'منطقي',
                        'json' => 'JSON',
                        default => $state,
                    }),
            ])
            ->defaultSort('group')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('المجموعة')
                    ->options([
                        'general' => 'عام',
                        'fulfillment' => 'التجهيز والتوصيل',
                        'payments' => 'الدفع',
                        'orders' => 'الطلبات',
                        'notifications' => 'الإشعارات',
                        'ai' => 'الذكاء الاصطناعي',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل إعداد')
                    ->modalWidth('2xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (($data['type'] ?? null) === 'boolean') {
                            $data['value'] = ! empty($data['value_bool']) ? '1' : '0';
                        }
                        if (($data['type'] ?? null) === 'json' && array_key_exists('value_json', $data)) {
                            $data['value'] = $data['value_json'];
                        }
                        unset($data['value_bool'], $data['value_json']);

                        return $data;
                    })
                    ->after(fn () => Setting::forgetCache()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSettings::route('/'),
        ];
    }
}
