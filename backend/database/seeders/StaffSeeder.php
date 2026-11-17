<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            [
                'email' => 'sales@sweets.ly',
                'name' => 'سارة المبيعات',
                'phone' => '0911111001',
                'role' => Role::SalesManager,
            ],
            [
                'email' => 'orders@sweets.ly',
                'name' => 'محمد الطلبات',
                'phone' => '0911111002',
                'role' => Role::OrderStaff,
            ],
            [
                'email' => 'accountant@sweets.ly',
                'name' => 'خالد المحاسب',
                'phone' => '0911111003',
                'role' => Role::Accountant,
            ],
            [
                'email' => 'content@sweets.ly',
                'name' => 'ليلى المحتوى',
                'phone' => '0911111004',
                'role' => Role::ContentSupervisor,
            ],
            [
                'email' => 'purchasing@sweets.ly',
                'name' => 'يوسف المشتريات',
                'phone' => '0911111005',
                'role' => Role::PurchasingSupervisor,
            ],
        ];

        foreach ($staff as $row) {
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'password' => 'password',
                    'is_active' => true,
                    'allowed_modules' => null,
                ]
            );
            $user->syncRoles([$row['role']->value]);
        }
    }
}
