<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'غير مدفوع',
            self::Partial => 'مدفوع جزئياً',
            self::Paid => 'مدفوع بالكامل',
        };
    }
}
