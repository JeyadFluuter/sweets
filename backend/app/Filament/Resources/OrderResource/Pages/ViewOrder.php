<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Models\RejectionReason;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'تفاصيل الطلب';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('saveNote')
                ->label('حفظ الملاحظة')
                ->icon('heroicon-o-pencil-square')
                ->form([
                    Forms\Components\Textarea::make('admin_note')
                        ->label('ملاحظة الإدارة')
                        ->default(fn () => $this->record->admin_note)
                        ->required(false),
                ])
                ->action(function (array $data) {
                    $this->record->update(['admin_note' => $data['admin_note'] ?? null]);
                    Notification::make()->title('تم حفظ الملاحظة')->success()->send();
                }),
            Actions\Action::make('accept')
                ->label('قبول')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn () => $this->record->status === OrderStatus::PendingReview)
                ->requiresConfirmation()
                ->action(function () {
                    app(OrderService::class)->accept($this->record, auth()->user());
                    $this->refreshFormData(['status', 'admin_note']);
                    $this->record->refresh();
                    Notification::make()->title('تم قبول الطلب')->success()->send();
                }),
            Actions\Action::make('reject')
                ->label('رفض')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->record->status === OrderStatus::PendingReview)
                ->form([
                    Forms\Components\Select::make('reason_id')
                        ->label('سبب الرفض')
                        ->options(RejectionReason::query()->where('is_active', true)->pluck('name', 'id'))
                        ->required(),
                    Forms\Components\Textarea::make('note')->label('ملاحظة'),
                ])
                ->action(function (array $data) {
                    app(OrderService::class)->reject(
                        $this->record,
                        auth()->user(),
                        (int) $data['reason_id'],
                        $data['note'] ?? null
                    );
                    $this->record->refresh();
                    Notification::make()->title('تم رفض الطلب')->danger()->send();
                }),
            Actions\Action::make('suggestSlot')
                ->label('اقتراح موعد')
                ->icon('heroicon-o-calendar')
                ->visible(fn () => $this->record->status === OrderStatus::PendingReview)
                ->form([
                    Forms\Components\DatePicker::make('date')->label('التاريخ')->required(),
                    Forms\Components\TimePicker::make('time')->label('الوقت')->seconds(false)->required(),
                    Forms\Components\Textarea::make('note')->label('ملاحظة'),
                ])
                ->action(function (array $data) {
                    app(OrderService::class)->suggestSlot(
                        $this->record,
                        auth()->user(),
                        $data['date'],
                        \Illuminate\Support\Carbon::parse($data['time'])->format('H:i'),
                        $data['note'] ?? null
                    );
                    $this->record->refresh();
                    Notification::make()->title('تم اقتراح موعد بديل')->success()->send();
                }),
            Actions\Action::make('recordPayment')
                ->label('تسجيل دفعة')
                ->icon('heroicon-o-banknotes')
                ->visible(fn () => (float) $this->record->remaining_amount > 0
                    && ! in_array($this->record->status, [OrderStatus::Rejected, OrderStatus::Cancelled, OrderStatus::Expired], true))
                ->form([
                    Forms\Components\Select::make('type')
                        ->label('النوع')
                        ->options(['advance' => 'مقدم', 'remainder' => 'متبقي'])
                        ->required(),
                    Forms\Components\TextInput::make('amount')->label('المبلغ')->numeric()->required()->minValue(0.01),
                    Forms\Components\Select::make('method')
                        ->label('الطريقة')
                        ->options([
                            'cash' => 'نقداً',
                            'transfer' => 'تحويل',
                            'card_on_delivery' => 'بطاقة عند الاستلام',
                            'manual' => 'يدوي',
                        ])
                        ->default('cash'),
                    Forms\Components\TextInput::make('reference')->label('المرجع'),
                ])
                ->action(function (array $data) {
                    app(PaymentService::class)->recordManual($this->record, $data, auth()->user());
                    $this->record->refresh();
                    Notification::make()->title('تم تسجيل الدفعة')->success()->send();
                }),
            Actions\Action::make('markPreparing')
                ->label('قيد التحضير')
                ->visible(fn () => $this->record->status === OrderStatus::Confirmed)
                ->action(function () {
                    app(OrderService::class)->transition($this->record, OrderStatus::Preparing, auth()->user());
                    $this->record->refresh();
                    Notification::make()->title('تم تحديث الحالة')->success()->send();
                }),
            Actions\Action::make('markReady')
                ->label('جاهز')
                ->visible(fn () => $this->record->status === OrderStatus::Preparing)
                ->action(function () {
                    app(OrderService::class)->transition($this->record, OrderStatus::Ready, auth()->user());
                    $this->record->refresh();
                    Notification::make()->title('تم تحديث الحالة')->success()->send();
                }),
            Actions\Action::make('markCompleted')
                ->label('مكتمل')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, [OrderStatus::Ready, OrderStatus::OutForDelivery], true))
                ->requiresConfirmation()
                ->action(function () {
                    app(OrderService::class)->transition($this->record, OrderStatus::Completed, auth()->user());
                    $this->record->refresh();
                    Notification::make()->title('تم إكمال الطلب')->success()->send();
                }),
        ];
    }
}
