<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'العملاء';

    protected static ?string $modelLabel = 'عميل';

    protected static ?string $pluralModelLabel = 'العملاء';

    protected static ?string $navigationGroup = AdminNavigation::PEOPLE;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('الاسم'),
            Forms\Components\TextInput::make('phone')->label('الهاتف')->tel(),
            Forms\Components\TextInput::make('email')->label('البريد')->email(),
            Forms\Components\Toggle::make('is_blocked')->label('محظور'),
            Forms\Components\Textarea::make('internal_notes')->label('ملاحظات داخلية')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('الهاتف')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('البريد')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_guest')->label('ضيف')->boolean(),
                Tables\Columns\IconColumn::make('is_blocked')->label('محظور')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('التسجيل')->dateTime('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_blocked')->label('محظور'),
                Tables\Filters\TernaryFilter::make('is_guest')->label('ضيف'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\Action::make('toggleBlock')
                    ->label(fn (Customer $record) => $record->is_blocked ? 'فك الحظر' : 'حظر')
                    ->icon(fn (Customer $record) => $record->is_blocked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (Customer $record) => $record->is_blocked ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(fn (Customer $record) => $record->update(['is_blocked' => ! $record->is_blocked])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCustomers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
