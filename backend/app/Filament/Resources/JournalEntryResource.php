<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Filament\Resources\JournalEntryResource\Pages;
use App\Models\Account;
use App\Models\JournalEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'القيود اليومية';

    protected static ?string $modelLabel = 'قيد يومية';

    protected static ?string $pluralModelLabel = 'القيود اليومية';

    protected static ?string $navigationGroup = AdminNavigation::FINANCE;

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['lines.account']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('entry_date')->label('تاريخ القيد')->required()->default(today()),
            Forms\Components\TextInput::make('memo')->label('البيان')->required()->columnSpanFull(),
            Forms\Components\Repeater::make('lines')
                ->label('أسطر القيد')
                ->schema([
                    Forms\Components\Select::make('account_code')
                        ->label('الحساب')
                        ->options(fn () => Account::query()->where('is_active', true)->orderBy('code')->get()->mapWithKeys(
                            fn (Account $a) => [$a->code => "{$a->code} — {$a->name}"]
                        ))
                        ->required()
                        ->searchable(),
                    Forms\Components\TextInput::make('debit')->label('مدين')->numeric()->default(0)->minValue(0),
                    Forms\Components\TextInput::make('credit')->label('دائن')->numeric()->default(0)->minValue(0),
                    Forms\Components\TextInput::make('memo')->label('ملاحظة'),
                ])
                ->columns(4)
                ->minItems(2)
                ->required()
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('الرقم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('entry_date')->label('التاريخ')->date('Y-m-d')->sortable(),
                Tables\Columns\TextColumn::make('memo')->label('البيان')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('debit_total')
                    ->label('مدين')
                    ->state(fn (JournalEntry $r) => number_format($r->totalDebit(), 2)),
                Tables\Columns\TextColumn::make('credit_total')
                    ->label('دائن')
                    ->state(fn (JournalEntry $r) => number_format($r->totalCredit(), 2)),
                Tables\Columns\IconColumn::make('balanced')
                    ->label('متوازن')
                    ->boolean()
                    ->state(fn (JournalEntry $r) => $r->isBalanced()),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
        ];
    }
}
