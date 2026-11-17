<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'shop_name', 'value' => 'معمل الحلويات', 'group' => 'general', 'type' => 'string'],
            ['key' => 'currency', 'value' => 'LYD', 'group' => 'general', 'type' => 'string'],
            ['key' => 'currency_label', 'value' => 'د.ل', 'group' => 'general', 'type' => 'string'],
            ['key' => 'timezone', 'value' => 'Africa/Tripoli', 'group' => 'general', 'type' => 'string'],
            ['key' => 'min_lead_hours', 'value' => '4', 'group' => 'fulfillment', 'type' => 'integer'],
            ['key' => 'max_advance_days', 'value' => '30', 'group' => 'fulfillment', 'type' => 'integer'],
            ['key' => 'daily_capacity', 'value' => '50', 'group' => 'fulfillment', 'type' => 'integer'],
            ['key' => 'delivery_fee', 'value' => '10', 'group' => 'fulfillment', 'type' => 'float'],
            ['key' => 'cancel_policy_hours', 'value' => '24', 'group' => 'orders', 'type' => 'integer'],
            ['key' => 'cancel_policy_refund_percent', 'value' => '50', 'group' => 'orders', 'type' => 'integer'],
            ['key' => 'min_advance_percent', 'value' => '25', 'group' => 'payments', 'type' => 'integer'],
            ['key' => 'payment_due_hours', 'value' => '24', 'group' => 'payments', 'type' => 'integer'],
            ['key' => 'remaining_reminder_time', 'value' => '08:00', 'group' => 'notifications', 'type' => 'string'],
        ];

        foreach ($defaults as $item) {
            Setting::setValue(
                $item['key'],
                $item['value'],
                $item['group'],
                $item['type']
            );
        }
    }
}
