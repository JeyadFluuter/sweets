<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingReview = 'pending_review';
    case AwaitingPayment = 'awaiting_payment';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case OutForDelivery = 'out_for_delivery';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'بانتظار المراجعة',
            self::AwaitingPayment => 'بانتظار الدفع',
            self::Confirmed => 'مؤكد',
            self::Preparing => 'قيد التحضير',
            self::Ready => 'جاهز',
            self::OutForDelivery => 'خرج للتوصيل',
            self::Completed => 'مكتمل',
            self::Rejected => 'مرفوض',
            self::Expired => 'منتهي المهلة',
            self::Cancelled => 'ملغى',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingReview => [self::AwaitingPayment, self::Rejected, self::Cancelled],
            self::AwaitingPayment => [self::Confirmed, self::Expired, self::Cancelled, self::Rejected],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::Ready, self::Cancelled],
            self::Ready => [self::OutForDelivery, self::Completed, self::Cancelled],
            self::OutForDelivery => [self::Completed, self::Cancelled],
            self::Completed, self::Rejected, self::Expired, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function reservesCapacity(): bool
    {
        return in_array($this, [
            self::PendingReview,
            self::AwaitingPayment,
            self::Confirmed,
            self::Preparing,
            self::Ready,
            self::OutForDelivery,
        ], true);
    }

    public function confirmsCapacity(): bool
    {
        return in_array($this, [
            self::Confirmed,
            self::Preparing,
            self::Ready,
            self::OutForDelivery,
            self::Completed,
        ], true);
    }
}
