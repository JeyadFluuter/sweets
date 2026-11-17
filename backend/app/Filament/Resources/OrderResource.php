<?php

namespace App\Filament\Resources;

use App\Support\AdminNavigation;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\RejectionReason;
use App\Services\Fulfillment\FulfillmentService;
use App\Services\Orders\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'الطلبات';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'الطلبات';

    protected static ?string $navigationGroup = AdminNavigation::OPERATIONS;

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Order::query()->where('status', OrderStatus::PendingReview)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('number')->label('رقم الطلب')->content(fn (?Order $record) => $record?->number),
            Forms\Components\Placeholder::make('status_label')->label('الحالة')->content(fn (?Order $record) => $record?->status?->label()),
            Forms\Components\Textarea::make('admin_note')->label('ملاحظة الإدارة')->columnSpanFull(),
            Forms\Components\Placeholder::make('review')->label('مؤشر المراجعة')
                ->content(function (?Order $record) {
                    if (! $record) {
                        return '—';
                    }
                    $review = app(FulfillmentService::class)->reviewIndicators($record);

                    return ($review['ok'] ? '✅ جاهز للقبول' : '⚠️ ').implode(' | ', $review['warnings'] ?: ['لا تنبيهات']);
                })
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'items']))
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('الرقم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('العميل')->searchable(),
                Tables\Columns\TextColumn::make('customer.phone')->label('الهاتف'),
                Tables\Columns\TextColumn::make('fulfillment_date')->label('الموعد')->date('Y-m-d')->sortable(),
                Tables\Columns\TextColumn::make('fulfillment_time')->label('الوقت'),
                Tables\Columns\TextColumn::make('total')->label('الإجمالي')->money('LYD'),
                Tables\Columns\TextColumn::make('remaining_amount')->label('المتبقي')->money('LYD'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => $state?->label())
                    ->color(fn ($state) => match ($state) {
                        OrderStatus::PendingReview => 'warning',
                        OrderStatus::AwaitingPayment => 'info',
                        OrderStatus::Confirmed, OrderStatus::Completed => 'success',
                        OrderStatus::Rejected, OrderStatus::Expired, OrderStatus::Cancelled => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('fulfillment_date')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\Filter::make('has_remaining')
                    ->label('عليه متبقٍ')
                    ->query(fn (Builder $q) => $q->where('remaining_amount', '>', 0)),
                Tables\Filters\Filter::make('today')
                    ->label('اليوم')
                    ->query(fn (Builder $q) => $q->whereDate('fulfillment_date', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\Action::make('accept')
                    ->label('قبول')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::PendingReview)
                    ->requiresConfirmation()
                    ->action(function (Order $record) {
                        app(OrderService::class)->accept($record, auth()->user());
                        Notification::make()->title('تم قبول الطلب')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::PendingReview)
                    ->form([
                        Forms\Components\Select::make('reason_id')
                            ->label('سبب الرفض')
                            ->options(RejectionReason::query()->where('is_active', true)->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Textarea::make('note')->label('ملاحظة'),
                    ])
                    ->action(function (Order $record, array $data) {
                        app(OrderService::class)->reject($record, auth()->user(), (int) $data['reason_id'], $data['note'] ?? null);
                        Notification::make()->title('تم رفض الطلب')->danger()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}

