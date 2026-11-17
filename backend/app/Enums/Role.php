<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case SalesManager = 'sales_manager';
    case Accountant = 'accountant';
    case ContentSupervisor = 'content_supervisor';
    case OrderStaff = 'order_staff';
    case PurchasingSupervisor = 'purchasing_supervisor';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدير عام',
            self::SalesManager => 'مدير مبيعات',
            self::Accountant => 'محاسب',
            self::ContentSupervisor => 'مشرف محتوى',
            self::OrderStaff => 'موظف طلبات',
            self::PurchasingSupervisor => 'مشرف مشتريات',
        };
    }
}
