<?php

namespace Database\Seeders;

use App\Models\BusinessHour;
use App\Models\DeliverySlot;
use App\Models\RejectionReason;
use Illuminate\Database\Seeder;

class FulfillmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(0, 6) as $day) {
            // الجمعة مغلقة كمثال — تُدار من ساعات العمل فقط
            $closed = $day === 5;
            BusinessHour::query()->updateOrCreate(
                ['day_of_week' => $day],
                [
                    'open_at' => $closed ? null : '09:00:00',
                    'close_at' => $closed ? null : '22:00:00',
                    'is_closed' => $closed,
                ]
            );
        }

        $slots = [
            ['10:00:00', '12:00:00', 8],
            ['12:00:00', '14:00:00', 10],
            ['16:00:00', '18:00:00', 10],
            ['18:00:00', '20:00:00', 8],
            ['20:00:00', '22:00:00', 6],
        ];

        foreach ($slots as [$start, $end, $max]) {
            DeliverySlot::query()->updateOrCreate(
                ['start_time' => $start, 'end_time' => $end],
                ['max_orders' => $max, 'type' => 'both', 'is_active' => true]
            );
        }

        foreach ([
            'ضيق الوقت',
            'اكتمال السعة',
            'منتج غير متاح',
            'غير ذلك',
        ] as $i => $name) {
            RejectionReason::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true, 'sort_order' => $i + 1]
            );
        }
    }
}
