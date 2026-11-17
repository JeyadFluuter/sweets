<?php

namespace App\Support;

/**
 * ترتيب مجموعات الشريط الجانبي في لوحة Filament.
 */
class AdminNavigation
{
    public const OPERATIONS = 'العمليات';

    public const CATALOG = 'الكتالوج';

    public const MARKETING = 'التسويق';

    public const SCHEDULE = 'المواعيد';

    public const INVENTORY = 'المخزون والمشتريات';

    public const FINANCE = 'المالية والمحاسبة';

    public const PEOPLE = 'العملاء والفريق';

    public const INTEGRATIONS = 'الإشعارات والتكاملات';

    public const SYSTEM = 'النظام';

    /**
     * @return list<string>
     */
    public static function groups(): array
    {
        return [
            self::OPERATIONS,
            self::CATALOG,
            self::MARKETING,
            self::SCHEDULE,
            self::INVENTORY,
            self::FINANCE,
            self::PEOPLE,
            self::INTEGRATIONS,
            self::SYSTEM,
        ];
    }

    public static function settingGroupLabel(string $group): string
    {
        return match ($group) {
            'general' => 'عام',
            'fulfillment' => 'التجهيز والتوصيل',
            'payments' => 'الدفع',
            'orders' => 'الطلبات',
            'notifications' => 'الإشعارات',
            'ai' => 'الذكاء الاصطناعي',
            default => $group,
        };
    }

    public static function settingKeyLabel(string $key): string
    {
        return match ($key) {
            'shop_name' => 'اسم المعمل',
            'currency' => 'العملة',
            'delivery_fee' => 'رسوم التوصيل',
            'min_lead_hours' => 'أقل مدة تجهيز (ساعات)',
            'payment_due_hours' => 'مهلة الدفع (ساعات)',
            'min_advance_percent' => 'أقل نسبة عربون %',
            'cancel_policy_hours' => 'مهلة الإلغاء قبل الموعد (ساعات)',
            'timezone' => 'المنطقة الزمنية',
            default => $key,
        };
    }
}
