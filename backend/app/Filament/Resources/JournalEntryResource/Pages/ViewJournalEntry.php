<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Filament\Resources\JournalEntryResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewJournalEntry extends ViewRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return parent::resolveRecord($key)->load(['lines.account', 'creator']);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('number')->label('الرقم'),
            Infolists\Components\TextEntry::make('entry_date')->label('التاريخ')->date('Y-m-d'),
            Infolists\Components\TextEntry::make('memo')->label('البيان')->columnSpanFull(),
            Infolists\Components\TextEntry::make('creator.name')->label('بواسطة'),
            Infolists\Components\RepeatableEntry::make('lines')
                ->label('الأسطر')
                ->schema([
                    Infolists\Components\TextEntry::make('account.code')->label('الرمز'),
                    Infolists\Components\TextEntry::make('account.name')->label('الحساب'),
                    Infolists\Components\TextEntry::make('debit')->label('مدين'),
                    Infolists\Components\TextEntry::make('credit')->label('دائن'),
                    Infolists\Components\TextEntry::make('memo')->label('ملاحظة'),
                ])
                ->columns(5)
                ->columnSpanFull(),
        ])->columns(3);
    }
}
