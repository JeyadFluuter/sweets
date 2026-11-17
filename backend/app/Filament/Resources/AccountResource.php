<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Enums\AccountType;
use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'دليل الحسابات';

    protected static ?string $modelLabel = 'حساب';

    protected static ?string $pluralModelLabel = 'دليل الحسابات';

    protected static ?string $navigationGroup = AdminNavigation::FINANCE;

    protected static ?int $navigationSort = 1;

    /** الحسابات تُزرع تلقائياً وتُستخدم من القيود — لا حاجة لها في الشريط الجانبي يومياً */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('الرمز')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->label('الاسم')->required(),
            Forms\Components\Select::make('type')
                ->label('النوع')
                ->options(collect(AccountType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                ->required(),
            Forms\Components\Textarea::make('description')->label('الوصف')->rows(2)->columnSpanFull(),
            Forms\Components\Toggle::make('is_active')->label('نشط')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الرمز')->searchable()->sortable()->badge(),
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('النوع')->formatStateUsing(fn ($state) => $state?->label()),
                Tables\Columns\TextColumn::make('balance')
                    ->label('الرصيد')
                    ->state(fn (Account $record) => number_format($record->balance(), 2).' د.ل'),
                Tables\Columns\IconColumn::make('is_active')->label('نشط')->boolean(),
            ])
            ->defaultSort('code')
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageAccounts::route('/')];
    }
}
