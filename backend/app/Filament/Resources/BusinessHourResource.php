<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessHourResource\Pages;
use App\Models\BusinessHour;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BusinessHourResource extends Resource
{
    protected static ?string $model = BusinessHour::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'ساعات العمل';

    protected static ?string $modelLabel = 'يوم عمل';

    protected static ?string $pluralModelLabel = 'ساعات العمل';

    protected static ?string $navigationGroup = AdminNavigation::SCHEDULE;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اليوم')
                ->schema([
                    Forms\Components\Select::make('day_of_week')
                        ->label('اليوم')
                        ->options([
                            0 => 'الأحد',
                            1 => 'الإثنين',
                            2 => 'الثلاثاء',
                            3 => 'الأربعاء',
                            4 => 'الخميس',
                            5 => 'الجمعة',
                            6 => 'السبت',
                        ])
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->native(false)
                        ->disabledOn('edit'),
                    Forms\Components\Toggle::make('is_closed')
                        ->label('مغلق طوال اليوم')
                        ->helperText('إن فُعّل، لن تظهر مواعيد هذا اليوم في التطبيق (بدل صفحة أيام الإغلاق)')
                        ->live()
                        ->inline(false),
                ])->columns(2),
            Forms\Components\Section::make('أوقات العمل')
                ->description('تُستخدم لتصفية فترات التسليم المتاحة')
                ->schema([
                    Forms\Components\TimePicker::make('open_at')
                        ->label('يفتح')
                        ->seconds(false)
                        ->required(fn (Get $get) => ! $get('is_closed'))
                        ->disabled(fn (Get $get) => (bool) $get('is_closed')),
                    Forms\Components\TimePicker::make('close_at')
                        ->label('يغلق')
                        ->seconds(false)
                        ->required(fn (Get $get) => ! $get('is_closed'))
                        ->disabled(fn (Get $get) => (bool) $get('is_closed')),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $days = [
            0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء',
            4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
        ];

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('اليوم')
                    ->formatStateUsing(fn ($state) => $days[$state] ?? $state)
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('open_at')
                    ->label('يفتح')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, BusinessHour $record) => $record->is_closed ? '—' : substr((string) $state, 0, 5)),
                Tables\Columns\TextColumn::make('close_at')
                    ->label('يغلق')
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, BusinessHour $record) => $record->is_closed ? '—' : substr((string) $state, 0, 5)),
                Tables\Columns\IconColumn::make('is_closed')
                    ->label('مغلق؟')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('availability')
                    ->label('الحالة')
                    ->badge()
                    ->state(fn (BusinessHour $record) => $record->is_closed ? 'مغلق' : 'مفتوح')
                    ->color(fn (BusinessHour $record) => $record->is_closed ? 'danger' : 'success'),
            ])
            ->defaultSort('day_of_week')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modalHeading(fn (BusinessHour $record) => 'تعديل: '.($days[$record->day_of_week] ?? ''))
                    ->modalWidth('lg'),
            ])
            ->paginated(false);
    }

    public static function canCreate(): bool
    {
        return BusinessHour::query()->count() < 7;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageBusinessHours::route('/')];
    }
}
