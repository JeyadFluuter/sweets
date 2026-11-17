<?php

namespace App\Filament\Resources\StockItemResource\Pages;

use App\Filament\Resources\StockItemResource;
use App\Models\StockItem;
use App\Models\StockMovement;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Contracts\Support\Htmlable;

class ViewStockItem extends ViewRecord
{
    protected static string $resource = StockItemResource::class;

    protected static ?string $title = 'تفاصيل صنف المخزون';

    public function getHeading(): string|Htmlable
    {
        return $this->record->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $qty = number_format((float) $this->record->quantity, 3);
        $unit = $this->record->unit;

        return $this->record->isLow()
            ? "المخزون منخفض · المتبقي {$qty} {$unit}"
            : "المتوفر {$qty} {$unit}";
    }

    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return parent::resolveRecord($key)->load([
            'movements' => fn ($q) => $q->latest()->limit(40),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل')
                ->modalHeading('تعديل صنف المخزون')
                ->modalWidth('lg'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('حالة المخزون')
                ->icon('heroicon-o-cube')
                ->schema([
                    Infolists\Components\TextEntry::make('name')
                        ->label('اسم الصنف')
                        ->weight(FontWeight::Bold)
                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    Infolists\Components\TextEntry::make('unit')
                        ->label('وحدة القياس')
                        ->badge()
                        ->color('gray'),
                    Infolists\Components\TextEntry::make('quantity')
                        ->label('الكمية الحالية')
                        ->formatStateUsing(fn ($state, StockItem $record) => number_format((float) $state, 3).' '.$record->unit)
                        ->weight(FontWeight::Bold)
                        ->color(fn (StockItem $record) => $record->isLow() ? 'danger' : 'success')
                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    Infolists\Components\TextEntry::make('min_quantity')
                        ->label('حد التنبيه')
                        ->formatStateUsing(fn ($state, StockItem $record) => number_format((float) $state, 3).' '.$record->unit)
                        ->icon('heroicon-m-bell-alert'),
                    Infolists\Components\TextEntry::make('stock_status')
                        ->label('الحالة')
                        ->state(fn (StockItem $record) => $record->isLow() ? 'منخفض' : 'كافٍ')
                        ->badge()
                        ->color(fn (StockItem $record) => $record->isLow() ? 'danger' : 'success')
                        ->icon(fn (StockItem $record) => $record->isLow()
                            ? 'heroicon-m-exclamation-triangle'
                            : 'heroicon-m-check-circle'),
                    Infolists\Components\IconEntry::make('is_active')
                        ->label('نشط')
                        ->boolean(),
                ])
                ->columns(3),

            Infolists\Components\Section::make('حركة المخزون')
                ->icon('heroicon-o-arrows-right-left')
                ->description('آخر الحركات المرتبطة بهذا الصنف (شراء، صرف، تعديل)')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('movements')
                        ->hiddenLabel()
                        ->visible(fn (StockItem $record) => $record->movements->isNotEmpty())
                        ->schema([
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('التاريخ')
                                ->dateTime('Y-m-d H:i')
                                ->icon('heroicon-m-clock'),
                            Infolists\Components\TextEntry::make('type')
                                ->label('النوع')
                                ->badge()
                                ->formatStateUsing(fn (?string $state) => match ($state) {
                                    'in' => 'وارد',
                                    'out' => 'صادر',
                                    'adjust' => 'تعديل',
                                    default => $state ?? '—',
                                })
                                ->color(fn (?string $state) => match ($state) {
                                    'in' => 'success',
                                    'out' => 'danger',
                                    'adjust' => 'warning',
                                    default => 'gray',
                                }),
                            Infolists\Components\TextEntry::make('quantity')
                                ->label('الكمية')
                                ->formatStateUsing(function ($state, StockMovement $record) {
                                    $sign = $record->type === 'out' ? '−' : '+';
                                    $unit = $record->stockItem?->unit ?? $this->record->unit;

                                    return $sign.number_format((float) $state, 3).' '.$unit;
                                })
                                ->weight(FontWeight::SemiBold)
                                ->color(fn (StockMovement $record) => match ($record->type) {
                                    'in' => 'success',
                                    'out' => 'danger',
                                    default => 'warning',
                                }),
                            Infolists\Components\TextEntry::make('note')
                                ->label('البيان')
                                ->placeholder('—')
                                ->columnSpanFull(),
                        ])
                        ->columns(3),
                    Infolists\Components\TextEntry::make('no_movements')
                        ->hiddenLabel()
                        ->state('لا توجد حركات مسجّلة بعد')
                        ->visible(fn (StockItem $record) => $record->movements->isEmpty())
                        ->color('gray'),
                ]),
        ]);
    }
}
