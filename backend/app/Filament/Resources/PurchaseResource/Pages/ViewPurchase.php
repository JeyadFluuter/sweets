<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Purchase;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Contracts\Support\Htmlable;

class ViewPurchase extends ViewRecord
{
    protected static string $resource = PurchaseResource::class;

    protected static ?string $title = 'تفاصيل فاتورة الشراء';

    public function getHeading(): string|Htmlable
    {
        return $this->record->number;
    }

    public function getSubheading(): string|Htmlable|null
    {
        $supplier = $this->record->supplier?->name ?? '—';
        $date = $this->record->purchased_at?->format('Y-m-d') ?? '—';

        return "المورد: {$supplier} · التاريخ: {$date}";
    }

    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        return parent::resolveRecord($key)->load(['items.stockItem', 'supplier', 'creator']);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('ملخص الفاتورة')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Infolists\Components\TextEntry::make('number')
                        ->label('رقم الفاتورة')
                        ->weight(FontWeight::Bold)
                        ->copyable()
                        ->icon('heroicon-m-hashtag'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('الحالة')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => match ($state) {
                            'confirmed' => 'مؤكدة',
                            'draft' => 'مسودة',
                            'cancelled' => 'ملغاة',
                            default => $state ?? '—',
                        })
                        ->color(fn (?string $state) => match ($state) {
                            'confirmed' => 'success',
                            'draft' => 'warning',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('purchased_at')
                        ->label('تاريخ الشراء')
                        ->date('Y-m-d')
                        ->icon('heroicon-m-calendar-days'),
                    Infolists\Components\TextEntry::make('total')
                        ->label('الإجمالي')
                        ->money('LYD')
                        ->weight(FontWeight::Bold)
                        ->color('primary')
                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                    Infolists\Components\TextEntry::make('items_count')
                        ->label('عدد الأصناف')
                        ->state(fn (Purchase $record) => $record->items->count())
                        ->suffix(' صنف')
                        ->icon('heroicon-m-cube'),
                    Infolists\Components\TextEntry::make('creator.name')
                        ->label('سجّلها')
                        ->placeholder('—')
                        ->icon('heroicon-m-user'),
                ])
                ->columns(3),

            Infolists\Components\Section::make('المورد')
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    Infolists\Components\TextEntry::make('supplier.name')
                        ->label('الاسم')
                        ->weight(FontWeight::SemiBold),
                    Infolists\Components\TextEntry::make('supplier.phone')
                        ->label('الهاتف')
                        ->placeholder('—')
                        ->icon('heroicon-m-phone')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('supplier.email')
                        ->label('البريد')
                        ->placeholder('—')
                        ->icon('heroicon-m-envelope'),
                    Infolists\Components\TextEntry::make('supplier.address')
                        ->label('العنوان')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsible(),

            Infolists\Components\Section::make('أصناف الفاتورة')
                ->icon('heroicon-o-queue-list')
                ->description('الكميات التي دخلت المخزون من هذه الفاتورة')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            Infolists\Components\TextEntry::make('stockItem.name')
                                ->label('الصنف')
                                ->weight(FontWeight::SemiBold)
                                ->icon('heroicon-m-cube'),
                            Infolists\Components\TextEntry::make('quantity')
                                ->label('الكمية')
                                ->formatStateUsing(function ($state, $record) {
                                    $unit = $record->stockItem?->unit ?? '';

                                    return number_format((float) $state, 3).($unit !== '' ? " {$unit}" : '');
                                }),
                            Infolists\Components\TextEntry::make('unit_cost')
                                ->label('تكلفة الوحدة')
                                ->money('LYD'),
                            Infolists\Components\TextEntry::make('line_total')
                                ->label('إجمالي السطر')
                                ->money('LYD')
                                ->weight(FontWeight::Bold)
                                ->color('success'),
                        ])
                        ->columns(4),
                ]),

            Infolists\Components\Section::make('ملاحظات')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->schema([
                    Infolists\Components\TextEntry::make('notes')
                        ->hiddenLabel()
                        ->placeholder('لا توجد ملاحظات')
                        ->columnSpanFull()
                        ->prose(),
                ])
                ->visible(fn (Purchase $record) => filled($record->notes))
                ->collapsed(false),
        ]);
    }
}
