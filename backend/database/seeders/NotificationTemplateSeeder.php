<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['order_submitted', 'تم استلام طلبك', 'طلبك رقم :number قيد المراجعة.'],
            ['order_accepted', 'تمت الموافقة', 'تمت الموافقة على الطلب :number. يرجى دفع :total د.ل.'],
            ['order_rejected', 'تم رفض الطلب', 'تم رفض الطلب :number.'],
            ['payment_received', 'تأكيد الدفعة', 'تم استلام دفعتك لطلب :number. المتبقي :remaining د.ل.'],
            ['payment_expired', 'انتهت مهلة الدفع', 'انتهت مهلة دفع الطلب :number.'],
            ['order_status_preparing', 'بدأ التحضير', 'طلبك :number قيد التحضير.'],
            ['order_status_ready', 'طلبك جاهز', 'طلبك :number أصبح جاهزاً.'],
            ['order_status_out_for_delivery', 'خرج للتوصيل', 'طلبك :number في الطريق إليك.'],
            ['order_status_completed', 'اكتمل الطلب', 'شكراً لطلبك :number.'],
        ];

        foreach ($templates as [$key, $title, $body]) {
            NotificationTemplate::query()->updateOrCreate(
                ['key' => $key],
                ['title' => $title, 'body' => $body, 'is_active' => true]
            );
        }
    }
}
