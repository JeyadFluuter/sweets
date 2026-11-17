<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['أحمد علي', '0912001001', 'ahmed@example.com'],
            ['فاطمة حسن', '0912001002', 'fatima@example.com'],
            ['عمر سالم', '0922001003', 'omar@example.com'],
            ['نورة محمد', '0932001004', 'noura@example.com'],
            ['خالد إبراهيم', '0942001005', 'khaled@example.com'],
            ['مريم صالح', '0952001006', 'mariam@example.com'],
            ['يوسف عبدالله', '0912001007', 'yousef@example.com'],
            ['هند رمضان', '0922001008', 'hind@example.com'],
            ['سامي الطرابلسي', '0932001009', 'sami@example.com'],
            ['أسماء الزوي', '0942001010', 'asma@example.com'],
            ['ضيف تجريبي', null, null], // guest later
        ];

        foreach ($customers as [$name, $phone, $email]) {
            if ($phone === null) {
                Customer::query()->updateOrCreate(
                    ['name' => $name, 'is_guest' => true],
                    [
                        'phone' => null,
                        'email' => null,
                        'password' => null,
                        'is_guest' => true,
                        'is_blocked' => false,
                    ]
                );

                continue;
            }

            Customer::query()->updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'is_guest' => false,
                    'is_blocked' => false,
                    'phone_verified_at' => now(),
                ]
            );
        }

        // عميل محظور للاختبار
        Customer::query()->updateOrCreate(
            ['phone' => '0912999999'],
            [
                'name' => 'عميل محظور',
                'email' => 'blocked@example.com',
                'password' => Hash::make('password'),
                'is_guest' => false,
                'is_blocked' => true,
            ]
        );
    }
}
